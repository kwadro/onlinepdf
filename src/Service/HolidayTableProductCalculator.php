<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Recipe;
use App\Repository\RecipeServiceRepository;

class HolidayTableProductCalculator
{
    private const MEN_PORTION_FACTOR = 1.0;
    private const WOMEN_PORTION_FACTOR = 1.0;

    public function __construct(
        private readonly RecipeServiceRepository $recipeServiceRepository,
    ) {
    }

    /**
     * @param int[] $recipeIds
     *
     * @return array{
     *     items: list<array{
     *         ingredient_id: int|null,
     *         ingredient: string,
     *         unit: string,
     *         quantity: float,
     *         recipes: list<string>
     *     }>,
     *     guest_count: int,
     *     effective_portions: float,
     *     recipes: list<array{id: int|null, name: string, servings: int, scale: float}>
     * }
     */
    public function calculate(
        array $recipeIds,
        int $menCount,
        int $womenCount,
        int $siteId,
        int $localeId,
    ): array {
        $recipeIds = array_values(array_unique(array_filter(array_map('intval', $recipeIds))));
        if ($recipeIds === []) {
            return [
                'items' => [],
                'guest_count' => $menCount + $womenCount,
                'effective_portions' => 0.0,
                'recipes' => [],
            ];
        }

        $recipes = $this->recipeServiceRepository->findByIdsWithComponents($recipeIds, $siteId, $localeId);
        $effectivePortions = $this->resolveEffectivePortions($menCount, $womenCount);
        $merged = [];
        $recipeMeta = [];

        foreach ($recipes as $recipe) {
            $translation = $this->resolveTranslation($recipe, $localeId);
            if ($translation === null) {
                continue;
            }

            $servings = max((int) ($recipe->getServings() ?? 1), 1);
            $scale = $effectivePortions / $servings;
            $recipeName = (string) ($translation->getName() ?? ('Recipe #' . $recipe->getId()));

            $recipeMeta[] = [
                'id' => $recipe->getId(),
                'name' => $recipeName,
                'servings' => $servings,
                'scale' => round($scale, 2),
            ];

            foreach ($translation->getGroupcomponents() ?? [] as $groupComponent) {
                foreach ($groupComponent->getComponents() ?? [] as $component) {
                    $ingredient = $component->getIngredient();
                    $unit = $component->getUnit();
                    if ($ingredient === null || $unit === null) {
                        continue;
                    }

                    $ingredientId = $ingredient->getId();
                    $unitName = (string) ($unit->getName() ?? '');
                    $key = ($ingredientId ?? 0) . '|' . $unitName;
                    $scaledQuantity = ((float) ($component->getQuantity() ?? 0)) * $scale;

                    if (!isset($merged[$key])) {
                        $merged[$key] = [
                            'ingredient_id' => $ingredientId,
                            'ingredient' => (string) ($ingredient->getName() ?? ''),
                            'unit' => $unitName,
                            'quantity' => 0.0,
                            'recipes' => [],
                        ];
                    }

                    $merged[$key]['quantity'] += $scaledQuantity;
                    if (!in_array($recipeName, $merged[$key]['recipes'], true)) {
                        $merged[$key]['recipes'][] = $recipeName;
                    }
                }
            }
        }

        $items = array_values(array_map(static function (array $item): array {
            $item['quantity'] = round($item['quantity'], 2);

            return $item;
        }, $merged));

        usort($items, static fn (array $a, array $b): int => strcmp($a['ingredient'], $b['ingredient']));

        return [
            'items' => $items,
            'guest_count' => $menCount + $womenCount,
            'effective_portions' => round($effectivePortions, 2),
            'recipes' => $recipeMeta,
        ];
    }

    private function resolveEffectivePortions(int $menCount, int $womenCount): float
    {
        $genderBased = ($menCount * self::MEN_PORTION_FACTOR) + ($womenCount * self::WOMEN_PORTION_FACTOR);

        return max($genderBased, 1);
    }

    private function resolveTranslation(Recipe $recipe, int $localeId)
    {
        foreach ($recipe->getRecipetranslations() ?? [] as $translation) {
            if ($translation->getLocale()?->getId() === $localeId) {
                return $translation;
            }
        }

        return null;
    }
}
