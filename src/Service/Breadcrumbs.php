<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Entity\RecipeCategory;
use Symfony\Contracts\Translation\TranslatorInterface;

class Breadcrumbs
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }
    public function loadBreadCrumbsByCategory(?RecipeCategory $recipeCategory): array
    {
        $breadCrumbs =[];

        if ($recipeCategory) {
            $breadCrumbs[] = [
                'link' => null,
                'url' => $recipeCategory->getSlug(),
                'name' => $recipeCategory->getName()
            ];
            $parentCategory = $recipeCategory->getParent();
            while ($parentCategory->getId() !== 1) {
                $item = [
                    'link' => true,
                    'url' => $parentCategory->getSlug(),
                    'name' => $parentCategory->getName()
                ];
                array_unshift($breadCrumbs, $item);
                $parentCategory = $parentCategory->getParent();
            }
        }
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;
    }
    public function loadBreadCrumbsByRecipe(?Recipe $recipe): array
    {
        $breadCrumbs =[];

        if ($recipe) {
            $breadCrumbs[] = [
                'link' => null,
                'url' => $recipe->getTranslations()[0]->getSlug(),
                'name' => $recipe->getTranslations()[0]->getName()
            ];
            $categories = $recipe->getRecipecategorys();
            $category = $categories[0];
            $item = [
                'link' => true,
                'url' => $category->getSlug(),
                'name' => $category->getName()
            ];
            array_unshift($breadCrumbs, $item);

            $parentCategory = $category->getParent();
            while ($parentCategory->getId() !== 1) {
                $item = [
                    'link' => true,
                    'url' => $parentCategory->getSlug(),
                    'name' => $parentCategory->getName()
                ];
                array_unshift($breadCrumbs, $item);
                $parentCategory = $parentCategory->getParent();
            }
        }
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;
    }

    public function loadBreadCrumbsByAuthor($recipeAuthor): array
    {
        $breadCrumbs =[];

        if ($recipeAuthor) {
            $breadCrumbs[] = [
                'link' => null,
                'url' => $recipeAuthor->getTranslations()[0]->getSlug(),
                'name' => $recipeAuthor->getTranslations()[0]->getName()
            ];
        }
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;
    }
}
