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
                'url' => $recipe->getRecipeTranslations()[0]->getSlug(),
                'name' => $recipe->getRecipeTranslations()[0]->getName()
            ];
            $categories = $recipe->getRecipecategorys();
            if($categories->count() > 0){
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
        }
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;
    }

    public function loadBreadCrumbsByAuthor($user): array
    {
        $breadCrumbs =[];

        if ($user) {
            $breadCrumbs[] = [
                'link' => null,
                'url' => null,
                'name' => 'all of  @cook-'.$user->getId()
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

    public function loadBreadCrumbsByMenuItem($menuItem):array
    {
        $breadCrumbs = [];
        $breadCrumbs[] = [
            'link' => null,
            'url' => $menuItem->getTranslations()[0]->getUrl(),
            'name' => $menuItem->getTranslations()[0]->getName()
        ];
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;
    }

    public function loadBreadCrumbsByCatalog()
    {
        $breadCrumbs = [];
        $breadCrumbs[] = [
            'link' => null,
            'url' => null,
            'name' => $this->translator->trans('Catalog', [], 'messages')
        ];
        $item = [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages')
        ];
        array_unshift($breadCrumbs, $item);
        return $breadCrumbs;

    }
}
