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

    /**
     * @param list<array<string, mixed>> $menu
     *
     * @return list<array{link: bool|null, url: string|null, name: string, route?: string, routeParams?: array<string, mixed>}>
     */
    public function resolveFromRequest(\Symfony\Component\HttpFoundation\Request $request, array $menu = []): array
    {
        $route = (string) $request->attributes->get('_route', '');

        if ($route === '' || in_array($route, ['homepage', 'default'], true)) {
            return [];
        }

        if (in_array($route, ['collection_list', 'catalog_show', 'author_list', 'catalog_list'], true)) {
            return [];
        }

        foreach ($menu as $item) {
            if (($item['url'] ?? '') === $route && !empty($item['name'])) {
                return $this->simpleTrail((string) $item['name']);
            }
        }

        return match ($route) {
            'search_page' => $this->searchTrail((string) $request->attributes->get('keyword', '')),
            'app_login' => $this->simpleTrail($this->translator->trans('Login')),
            'app_register' => $this->simpleTrail($this->translator->trans('Register')),
            'account_setting' => $this->simpleTrail($this->translator->trans('Profile')),
            'account_my_recipes' => $this->simpleTrail($this->translator->trans('My Recipes')),
            'account_recently_viewed' => $this->simpleTrail($this->translator->trans('Recently viewed')),
            'account_wishlist_page' => $this->simpleTrail($this->translator->trans('Favorite Recipe')),
            'account_plan' => $this->simpleTrail($this->translator->trans('subscription.your_plan')),
            'privacy_policy' => $this->simpleTrail($this->translator->trans('Privacy Policy')),
            'recipe_edit' => $this->simpleTrail($this->translator->trans('Edit')),
            'add_recipe' => $this->simpleTrail($this->translator->trans('Add new recipe')),
            default => [],
        };
    }

    /**
     * @return list<array{link: bool|null, url: string|null, name: string}>
     */
    private function simpleTrail(string $currentName): array
    {
        return [
            $this->homeItem(),
            [
                'link' => null,
                'url' => null,
                'name' => $currentName,
            ],
        ];
    }

    /**
     * @return list<array{link: bool|null, url: string|null, name: string}>
     */
    private function searchTrail(string $keyword): array
    {
        $label = $this->translator->trans('Search');
        if ($keyword !== '') {
            $label .= ': ' . $keyword;
        }

        return $this->simpleTrail($label);
    }

    /**
     * @return array{link: true, url: string, name: string}
     */
    private function homeItem(): array
    {
        return [
            'link' => true,
            'url' => 'home',
            'name' => $this->translator->trans('Home', [], 'messages'),
        ];
    }
}
