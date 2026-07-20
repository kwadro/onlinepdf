<?php

declare(strict_types=1);

namespace App\Service\Recipe;

use App\Entity\Component;
use App\Entity\GroupComponent;
use App\Entity\Ingredient;
use App\Entity\Locale;
use App\Entity\Recipe;
use App\Entity\RecipeCategory;
use App\Entity\RecipeStep;
use App\Entity\RecipeTranslation;
use App\Entity\Site;
use App\Entity\Unit;
use App\Entity\User;
use App\Import\RecipeCatalogImportResult;
use Doctrine\ORM\EntityManagerInterface;

final class RecipeCatalogJsonImporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function importFromFile(string $filePath): RecipeCatalogImportResult
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $filePath));
        }

        $payload = json_decode((string) file_get_contents($filePath), true);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON file.');
        }

        return $this->import($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function import(array $payload): RecipeCatalogImportResult
    {
        $result = new RecipeCatalogImportResult();

        if (($payload['schema_version'] ?? null) !== 1) {
            throw new \InvalidArgumentException('Unsupported schema_version. Expected 1.');
        }

        $siteDomain = trim((string) ($payload['site'] ?? ''));
        if ($siteDomain === '') {
            throw new \InvalidArgumentException('Field "site" is required.');
        }

        $site = $this->entityManager->getRepository(Site::class)->findOneBy(['domain' => $siteDomain]);
        if (!$site instanceof Site) {
            throw new \InvalidArgumentException(sprintf('Site with domain "%s" was not found.', $siteDomain));
        }

        $recipes = $payload['recipes'] ?? null;
        if (!is_array($recipes) || $recipes === []) {
            throw new \InvalidArgumentException('Field "recipes" must be a non-empty array.');
        }

        $this->entityManager->wrapInTransaction(function () use ($payload, $site, $recipes, $result): void {
            if (isset($payload['reference_data']) && is_array($payload['reference_data'])) {
                $this->importReferenceData($payload['reference_data'], $result);
            }

            foreach ($recipes as $index => $recipeData) {
                if (!is_array($recipeData)) {
                    $result->addError(sprintf('Recipe #%d is not an object.', $index + 1));
                    continue;
                }

                try {
                    $imported = $this->importRecipe($site, $recipeData);
                    $result->recipeIds[] = (int) $imported['recipe']->getId();
                    if ($imported['is_new']) {
                        ++$result->recipesCreated;
                    } else {
                        ++$result->recipesUpdated;
                    }
                } catch (\Throwable $exception) {
                    $result->addError(sprintf('Recipe #%d: %s', $index + 1, $exception->getMessage()));
                }
            }
        });

        return $result;
    }

    /**
     * @param array<string, mixed> $referenceData
     */
    private function importReferenceData(array $referenceData, RecipeCatalogImportResult $result): void
    {
        foreach ($referenceData['units'] ?? [] as $unitData) {
            if (is_array($unitData)) {
                $this->resolveUnit($unitData);
            }
        }

        foreach ($referenceData['ingredients'] ?? [] as $ingredientData) {
            if (is_array($ingredientData)) {
                $this->resolveIngredient($ingredientData);
            }
        }

        foreach ($referenceData['categories'] ?? [] as $categoryData) {
            if (is_array($categoryData)) {
                $this->resolveCategory($categoryData);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $recipeData
     *
     * @return array{recipe: Recipe, is_new: bool}
     */
    private function importRecipe(Site $site, array $recipeData): array
    {

        $recipe = $this->resolveRecipe($site, $recipeData);
        $isNew = $recipe->getId() === null;
        $recipe->setSite($site);
        $recipe->setPosition($this->intOrNull($recipeData['position'] ?? null));
        $recipe->setPrepTimeMin($this->intOrNull($recipeData['prep_time_min'] ?? null));
        $recipe->setCookTimeMin($this->intOrNull($recipeData['cook_time_min'] ?? null));
        $recipe->setServings($this->intOrNull($recipeData['servings'] ?? null));
        $recipe->setImage($this->stringOrNull($recipeData['image'] ?? null));

        foreach ($recipe->getRecipecategorys()?->toArray() ?? [] as $existingCategory) {
            $recipe->removeRecipecategory($existingCategory);
        }

        foreach ($recipeData['categories'] ?? [] as $categoryName) {
            $category = $this->entityManager->getRepository(RecipeCategory::class)->findOneBy([
                'name' => (string) $categoryName,
            ]);
            if (!$category instanceof RecipeCategory) {
                throw new \InvalidArgumentException(sprintf('Category "%s" was not found.', (string) $categoryName));
            }
            $recipe->addRecipecategory($category);
        }

        $this->entityManager->persist($recipe);
        $this->entityManager->flush();

        $translations = $recipeData['translations'] ?? [];
        if (!is_array($translations) || $translations === []) {
            throw new \InvalidArgumentException('Each recipe must contain at least one translation.');
        }

        foreach ($translations as $translationData) {
            if (!is_array($translationData)) {
                continue;
            }
            $this->importTranslation($recipe, $translationData);
        }

        $this->entityManager->flush();

        return ['recipe' => $recipe, 'is_new' => $isNew];
    }

    /**
     * @param array<string, mixed> $recipeData
     */
    private function resolveRecipe(Site $site, array $recipeData): Recipe
    {
        $recipeRepo = $this->entityManager->getRepository(Recipe::class);

        if (isset($recipeData['id']) && (int)$recipeData['id'] > 0) {
            $recipe = $recipeRepo->find((int) $recipeData['id']);
            if ($recipe instanceof Recipe && $recipe->getSite()?->getDomain() === $site->getDomain()) {
                return $recipe;
            }
        }

        $externalKey = trim((string) ($recipeData['external_key'] ?? ''));
        if ($externalKey !== '') {
            $existingTranslation = $this->findTranslationBySlug($site, $externalKey);
            if ($existingTranslation instanceof RecipeTranslation && $existingTranslation->getRecipe() instanceof Recipe) {
                return $existingTranslation->getRecipe();
            }
        }

        $translations = $recipeData['translations'] ?? [];
        if (is_array($translations)) {
            foreach ($translations as $translationData) {
                if (!is_array($translationData)) {
                    continue;
                }
                $slug = trim((string) ($translationData['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $existingTranslation = $this->findTranslationBySlug($site, $slug);
                if ($existingTranslation instanceof RecipeTranslation && $existingTranslation->getRecipe() instanceof Recipe) {
                    return $existingTranslation->getRecipe();
                }
            }
        }
        $recipe =  new Recipe();
        $recipe->setId(null);
        return $recipe;

    }

    private function findTranslationBySlug(Site $site, string $slug): ?RecipeTranslation
    {
        return $this->entityManager->createQueryBuilder()
            ->select('rt')
            ->from(RecipeTranslation::class, 'rt')
            ->innerJoin('rt.recipe', 'r')
            ->innerJoin('r.site', 's')
            ->andWhere('rt.slug = :slug')
            ->andWhere('s.domain = :domain')
            ->setParameter('slug', $slug)
            ->setParameter('domain', $site->getDomain())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array<string, mixed> $translationData
     */
    private function importTranslation(Recipe $recipe, array $translationData): void
    {
        $locale = $this->resolveLocale((string) ($translationData['locale'] ?? ''));
        if (!$locale instanceof Locale) {
            throw new \InvalidArgumentException(sprintf('Locale "%s" was not found.', (string) ($translationData['locale'] ?? '')));
        }

        $translation = null;
        foreach ($recipe->getRecipetranslations()?->toArray() ?? [] as $existingTranslation) {
            if ($existingTranslation->getLocale()?->getId() === $locale->getId()) {
                $translation = $existingTranslation;
                break;
            }
        }

        if (!$translation instanceof RecipeTranslation) {
            $translation = new RecipeTranslation();
            $recipe->addRecipeTranslation($translation);
        }

        $translation->setLocale($locale);
        $translation->setName($this->stringOrNull($translationData['name'] ?? null));
        $translation->setSlug($this->stringOrNull($translationData['slug'] ?? null));
        $translation->setIsActive($this->yesNo($translationData['is_active'] ?? 'Yes'));
        $translation->setPublish($this->yesNo($translationData['publish'] ?? 'Yes'));
        $translation->setConfirmation($this->yesNo($translationData['confirmation'] ?? 'No'));
        $translation->setIsPopular($this->yesNo($translationData['is_popular'] ?? 'No'));
        $translation->setMetaTitle($this->stringOrNull($translationData['meta_title'] ?? null));
        $translation->setMetaDescription($this->stringOrNull($translationData['meta_description'] ?? null));
        $translation->setShortDescription($this->stringOrNull($translationData['short_description'] ?? null));
        $translation->setDescription($this->stringOrNull($translationData['description'] ?? null));
        $translation->setCuisine($this->stringOrNull($translationData['cuisine'] ?? null));
        $translation->setNotes($this->stringOrNull($translationData['notes'] ?? null));
        $translation->setFacebookImage($this->stringOrNull($translationData['facebook_image'] ?? null));

        $authorEmail = trim((string) ($translationData['author_email'] ?? ''));
        if ($authorEmail !== '') {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $authorEmail]);
            if (!$user instanceof User) {
                throw new \InvalidArgumentException(sprintf('User with email "%s" was not found.', $authorEmail));
            }
            $translation->setUser($user);
        }

        $this->clearTranslationChildren($translation);

        foreach ($translationData['group_components'] ?? [] as $groupIndex => $groupData) {
            if (!is_array($groupData)) {
                continue;
            }
            $this->importGroupComponent($translation, $groupData, $groupIndex + 1);
        }

        foreach ($translationData['steps'] ?? [] as $stepIndex => $stepData) {
            if (!is_array($stepData)) {
                continue;
            }
            $this->importStep($translation, $stepData, $stepIndex + 1);
        }

        $this->entityManager->persist($translation);
    }

    private function clearTranslationChildren(RecipeTranslation $translation): void
    {
        foreach ($translation->getGroupcomponents()?->toArray() ?? [] as $groupComponent) {
            foreach ($groupComponent->getComponents()?->toArray() ?? [] as $component) {
                $this->entityManager->remove($component);
            }
            $this->entityManager->remove($groupComponent);
        }

        foreach ($translation->getRecipesteps()?->toArray() ?? [] as $step) {
            $this->entityManager->remove($step);
        }

        $translation->getGroupcomponents()?->clear();
        $translation->getRecipesteps()?->clear();
    }

    /**
     * @param array<string, mixed> $groupData
     */
    private function importGroupComponent(RecipeTranslation $translation, array $groupData, int $fallbackPosition): void
    {
        $group = new GroupComponent();
        $group->setRecipetranslation($translation);
        $group->setName($this->stringOrNull($groupData['name'] ?? null));
        $group->setPosition($this->intOrNull($groupData['position'] ?? null) ?? $fallbackPosition);
        $translation->addGroupComponent($group);

        foreach ($groupData['components'] ?? [] as $componentIndex => $componentData) {
            if (!is_array($componentData)) {
                continue;
            }
            $this->importComponent($group, $componentData, $componentIndex + 1);
        }

        $this->entityManager->persist($group);
    }

    /**
     * @param array<string, mixed> $componentData
     */
    private function importComponent(GroupComponent $group, array $componentData, int $fallbackPosition): void
    {
        $ingredientName = trim((string) ($componentData['ingredient'] ?? ''));
        $unitShortName = trim((string) ($componentData['unit'] ?? ''));

        if ($ingredientName === '' || $unitShortName === '') {
            throw new \InvalidArgumentException('Component ingredient and unit are required.');
        }

        $ingredient = $this->resolveIngredient(['name' => $ingredientName]);
        $unit = $this->resolveUnit(['name' => $unitShortName, 'short_name' => $unitShortName]);

        $component = new Component();
        $component->setGroupcomponent($group);
        $component->setIngredient($ingredient);
        $component->setUnit($unit);
        $component->setQuantity($this->intOrNull($componentData['quantity'] ?? null));
        $component->setPosition($this->intOrNull($componentData['position'] ?? null) ?? $fallbackPosition);
        $group->addComponent($component);

        $this->entityManager->persist($component);
    }

    /**
     * @param array<string, mixed> $stepData
     */
    private function importStep(RecipeTranslation $translation, array $stepData, int $fallbackPosition): void
    {
        $step = new RecipeStep();
        $step->setRecipetranslation($translation);
        $step->setName($this->stringOrNull($stepData['name'] ?? null));
        $step->setPosition($this->intOrNull($stepData['position'] ?? null) ?? $fallbackPosition);
        $step->setQuestion($this->stringOrNull($stepData['question'] ?? null));
        $step->setAnswer($this->stringOrNull($stepData['answer'] ?? null));
        $step->setImage($this->stringOrNull($stepData['image'] ?? null));
        $translation->addRecipeStep($step);

        $this->entityManager->persist($step);
    }

    /**
     * @param array<string, mixed> $unitData
     */
    private function resolveUnit(array $unitData): Unit
    {
        $shortName = trim((string) ($unitData['short_name'] ?? $unitData['name'] ?? ''));
        if ($shortName === '') {
            throw new \InvalidArgumentException('Unit short_name is required.');
        }

        $unit = $this->entityManager->getRepository(Unit::class)->findOneBy(['short_name' => $shortName]);
        if ($unit instanceof Unit) {
            return $unit;
        }

        $unit = new Unit();
        $unit->setShortName($shortName);
        $unit->setName($this->stringOrNull($unitData['name'] ?? null) ?? $shortName);
        $this->entityManager->persist($unit);

        return $unit;
    }

    /**
     * @param array<string, mixed> $ingredientData
     */
    private function resolveIngredient(array $ingredientData): Ingredient
    {
        $name = trim((string) ($ingredientData['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Ingredient name is required.');
        }

        $ingredient = $this->entityManager->getRepository(Ingredient::class)->findOneBy(['name' => $name]);
        if ($ingredient instanceof Ingredient) {
            return $ingredient;
        }

        $ingredient = new Ingredient();
        $ingredient->setName($name);
        $ingredient->setSku($this->stringOrNull($ingredientData['sku'] ?? null));
        $ingredient->setUrl($this->stringOrNull($ingredientData['url'] ?? null));
        $ingredient->setPrice($this->stringOrNull($ingredientData['price'] ?? null));
        $this->entityManager->persist($ingredient);

        return $ingredient;
    }

    /**
     * @param array<string, mixed> $categoryData
     */
    private function resolveCategory(array $categoryData): RecipeCategory
    {
        $name = trim((string) ($categoryData['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }

        $category = $this->entityManager->getRepository(RecipeCategory::class)->findOneBy(['name' => $name]);
        if (!$category instanceof RecipeCategory) {
            $category = new RecipeCategory();
            $category->setName($name);
        }

        $category->setSlug($this->stringOrNull($categoryData['slug'] ?? null) ?? $name);
        $category->setPosition($this->intOrNull($categoryData['position'] ?? null));
        $category->setIsActive($this->yesNo($categoryData['is_active'] ?? 'Yes'));

        $parentName = trim((string) ($categoryData['parent'] ?? ''));
        if ($parentName !== '') {
            $parent = $this->entityManager->getRepository(RecipeCategory::class)->findOneBy(['name' => $parentName]);
            if ($parent instanceof RecipeCategory) {
                $category->setParent($parent);
            }
        }

        $this->entityManager->persist($category);

        return $category;
    }

    private function resolveLocale(string $localeKey): ?Locale
    {
        $localeKey = trim($localeKey);
        if ($localeKey === '') {
            return null;
        }

        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => $localeKey]);
        if ($locale instanceof Locale) {
            return $locale;
        }

        return $this->entityManager->getRepository(Locale::class)->findOneBy(['name' => $localeKey]);
    }

    private function yesNo(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['yes', '1', 'true'], true) ? 'Yes' : 'No';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
