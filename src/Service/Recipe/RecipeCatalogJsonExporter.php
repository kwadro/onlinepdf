<?php

declare(strict_types=1);

namespace App\Service\Recipe;

use App\Entity\Component;
use App\Entity\GroupComponent;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeCategory;
use App\Entity\RecipeStep;
use App\Entity\RecipeTranslation;
use App\Entity\Site;
use App\Entity\Unit;
use Doctrine\ORM\EntityManagerInterface;

final class RecipeCatalogJsonExporter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<int>|null $recipeIds
     *
     * @return array<string, mixed>
     */
    public function export(string $siteDomain, ?array $recipeIds = null, bool $includeReferenceData = true): array
    {
        $site = $this->entityManager->getRepository(Site::class)->findOneBy(['domain' => $siteDomain]);
        if (!$site instanceof Site) {
            throw new \InvalidArgumentException(sprintf('Site with domain "%s" was not found.', $siteDomain));
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Recipe::class, 'r')
            ->innerJoin('r.site', 's')
            ->andWhere('s.domain = :domain')
            ->setParameter('domain', $siteDomain)
            ->orderBy('r.position', 'ASC')
            ->addOrderBy('r.id', 'ASC');

        if ($recipeIds !== null && $recipeIds !== []) {
            $qb->andWhere('r.id IN (:ids)')->setParameter('ids', $recipeIds);
        }

        /** @var list<Recipe> $recipes */
        $recipes = $qb->getQuery()->getResult();

        $payload = [
            'schema_version' => 1,
            'site' => $siteDomain,
            'recipes' => array_map(fn(Recipe $recipe) => $this->exportRecipe($recipe), $recipes),
        ];

        if ($includeReferenceData) {
            $payload['reference_data'] = $this->exportReferenceData($recipes);
        }

        return $payload;
    }

    public function exportToFile(
        string $filePath,
        string $siteDomain,
        ?array $recipeIds = null,
        bool $includeReferenceData = true
    ): void {
        $payload = $this->export($siteDomain, $recipeIds, $includeReferenceData);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($filePath, $json) === false) {
            throw new \RuntimeException(sprintf('Unable to write file: %s', $filePath));
        }
    }

    /**
     * @param list<Recipe> $recipes
     *
     * @return array<string, mixed>
     */
    private function exportReferenceData(array $recipes): array
    {
        $units = [];
        $ingredients = [];
        $categories = [];

        foreach ($this->entityManager->getRepository(Unit::class)->findBy([], ['short_name' => 'ASC']) as $unit) {
            if (!$unit instanceof Unit) {
                continue;
            }
            $units[] = [
                'name' => $unit->getName(),
                'short_name' => $unit->getShortName(),
            ];
        }

        foreach ($this->entityManager->getRepository(Ingredient::class)->findBy([], ['name' => 'ASC']) as $ingredient) {
            if (!$ingredient instanceof Ingredient) {
                continue;
            }
            $ingredients[] = [
                'name' => $ingredient->getName(),
                'sku' => $ingredient->getSku(),
                'url' => $ingredient->getUrl(),
                'price' => $ingredient->getPrice(),
            ];
        }

        foreach (
            $this->entityManager->getRepository(RecipeCategory::class)->findBy([], ['position' => 'ASC']
            ) as $category
        ) {
            if (!$category instanceof RecipeCategory) {
                continue;
            }
            $categories[] = [
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'position' => $category->getPosition(),
                'is_active' => $category->getIsActive(),
                'parent' => $category->getParent()?->getName(),
            ];
        }

        return [
            'units' => $units,
            'ingredients' => $ingredients,
            'categories' => $categories,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportRecipe(Recipe $recipe): array
    {
        $primarySlug = $recipe->getRecipetranslations()?->first();
        $externalKey = $primarySlug instanceof RecipeTranslation ? $primarySlug->getSlug() : null;

        return [
            'id' => $recipe->getId(),
            'external_key' => $externalKey,
            'position' => $recipe->getPosition(),
            'prep_time_min' => $recipe->getPrepTimeMin(),
            'cook_time_min' => $recipe->getCookTimeMin(),
            'servings' => $recipe->getServings(),
            'image' => $recipe->getImage(),
            'categories' => array_values(
                array_map(
                    static fn(RecipeCategory $category) => [
                        'id' => (int)$category->getId(),
                        'name' => (string)$category->getName(),
                    ],
                    $recipe->getRecipecategorys()?->toArray() ?? [],
                )
            ),
            'translations' => array_values(
                array_map(
                    fn(RecipeTranslation $translation) => $this->exportTranslation($translation),
                    $recipe->getRecipetranslations()?->toArray() ?? [],
                )
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportTranslation(RecipeTranslation $translation): array
    {
        $locale = $translation->getLocale();

        return [
            'id' => $translation->getId(),
            'locale' => $locale?->getCode() ?? $locale?->getName(),
            'name' => $translation->getName(),
            'slug' => $translation->getSlug(),
            'is_active' => $translation->getIsActive(),
            'publish' => $translation->getPublish(),
            'confirmation' => $translation->getConfirmation(),
            'is_popular' => $translation->getIsPopular(),
            'meta_title' => $translation->getMetaTitle(),
            'meta_description' => $translation->getMetaDescription(),
            'short_description' => $translation->getShortDescription(),
            'description' => $translation->getDescription(),
            'cuisine' => $translation->getCuisine(),
            'notes' => $translation->getNotes(),
            'facebook_image' => $translation->getFacebookImage(),
            'author_email' => $translation->getUser()?->getEmail(),
            'group_components' => array_values(
                array_map(
                    fn(GroupComponent $group) => $this->exportGroupComponent($group),
                    $translation->getGroupcomponents()?->toArray() ?? [],
                )
            ),
            'steps' => array_values(
                array_map(
                    fn(RecipeStep $step) => $this->exportStep($step),
                    $translation->getRecipesteps()?->toArray() ?? [],
                )
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportGroupComponent(GroupComponent $group): array
    {
        return [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'position' => $group->getPosition(),
            'components' => array_values(
                array_map(
                    fn(Component $component) => $this->exportComponent($component),
                    $group->getComponents()?->toArray() ?? [],
                )
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportComponent(Component $component): array
    {
        return [
            'id' => $component->getId(),
            'position' => $component->getPosition(),
            'ingredient' => $component->getIngredient()?->getName(),
            'unit' => $component->getUnit()?->getShortName(),
            'quantity' => $component->getQuantity(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportStep(RecipeStep $step): array
    {
        return [
            'id' => $step->getId(),
            'name' => $step->getName(),
            'position' => $step->getPosition(),
            'question' => $step->getQuestion(),
            'answer' => $step->getAnswer(),
            'image' => $step->getImage(),
        ];
    }
}
