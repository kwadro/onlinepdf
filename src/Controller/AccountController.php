<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\RecipeTranslation;
use App\Form\RecipeType;
use App\Repository\RecipeRepository;
use App\Repository\RecipeServiceRepository;
use App\Repository\RecipeViewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AccountController extends AbstractController
{

    #[Route('/{_locale}/action-recipe', name: 'action_recipe')]
    public function actionRecipe(
        Request $request,
        EntityManagerInterface $entityManager,
        RecipeServiceRepository $recipeServiceRepository
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            $action = $request->request->get('action') ?? null;
            $recipeId = $request->request->get('recipe_id') ?? null;
            if (!$recipeId) {
                $recipeId = 1;
            }
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $recipe = $entityManager->getRepository(Recipe::class)->find($recipeId);
            if ($action === 'save_close' || $action === 'save') {
                $params = $request->request->all();
                $recipe->setCookTimeMin((int)$params['timeprepare']);
                $recipe->setPrepTimeMin((int)$params['timecook']);
                $recipe->setServings((int)$params['serving']);
                $translations = $recipe->getRecipetranslations();
                foreach ($translations as $translation) {
                    if ($translation->getLocale()->getId() === $localeObject->getId()) {
                        $translation->setShortDescription($params['short_description'] ?? null);
                        $translation->setDescription($params['description'] ?? null);
                        $translation->setName($params['title'] ?? null);

                        $entityManager->persist($translation);
                    }
                }

                $entityManager->persist($recipe);
                $entityManager->flush();
                if ($action === 'save_close') {
                    return $this->redirectToRoute('account_my_recipes');
                }
            }

            return $this->render('security/account/add-recipe.html.twig', [
                'user' => $user,
                'recipe' => $recipe,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/delete-recipe', name: 'delete_recipe')]
    public function deleteRecipe(
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            return $this->render('security/account/add-recipe.html.twig', [
                'user' => $user,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/save-close-recipe', name: 'save_close_recipe')]
    public function saveCloseRecipe(
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            return $this->render('security/account/add-recipe.html.twig', [
                'user' => $user,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/publish-recipe', name: 'publish_recipe')]
    public function publishRecipe(
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            return $this->render('security/account/add-recipe.html.twig', [
                'user' => $user,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/recipe/{id}/edit', name: 'recipe_edit')]
    public function edit(
        RecipeTranslation $recipe,
        Request $request,
        EntityManagerInterface $em,
        RecipeServiceRepository $recipeServiceRepository,
        $id
    ): Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $recipesIds = [$id];
            $localeObject = $request->attributes->get('localeObject');

            $recipes = $recipeServiceRepository->loadItemsByIds(
                $recipesIds,
                $site->getId(),
                $localeObject->getId()
            );
            if (!$recipes) {
                return $this->redirectToRoute('app_login');
            }
            $form = $this->createForm(
                RecipeType::class,
                $recipes[0],
                ['attr' => ['locale' => $localeObject->getCode()]]
            );

            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
            }
            $ajaxUrl = $this->generateUrl('recipe_image_update');
            return $this->render('security/account/edit-recipe.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
                'recipe' => $recipes[0],
                'ajaxUrl' => $ajaxUrl,

            ]);
        }
        return $this->redirectToRoute('app_login');
    }
    #[Route('/{_locale}/recipe-image-update', name: 'recipe_image_update')]
    public function recipeImageUpdate(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        $result = [];
        if ($request->isXmlHttpRequest()) {
            if ($this->getUser()) {
                $result['success'] = true;
                $formCode = $request->get('form_code');

                if ($formCode === 'recipe-image') {
                    $file = $request->files->get('recipe_file');
                    if (!$file) {
                        return $this->json(['error' => 'No file uploaded'], 400);
                    }
                    //  Validate mime type
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        return $this->json(['error' => 'Invalid file type'], 400);
                    }
                    //  Generate safe filename
                    $newFilename = uniqid() . '.' . $file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('app.recipe_upload_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Upload failed'], 500);
                    }
                    $recipeImageUrl = str_replace('/uploads/recipes/', '', $newFilename);
                    $recipe = $entityManager->getRepository(Recipe::class)->find($request->get('recipe_id'));
                    $recipe->setImage($newFilename);
                    $entityManager->persist($recipe);
                    $entityManager->flush();
                    $result['message'] = 'Save recipe success';
                    $result['imageUrl'] = $recipeImageUrl;
                }
                return $this->json($result);
            }
        }
        $result['status'] = false;
        return $this->json($result);
    }
    #[Route('/{_locale}/recipe/autosave', name: 'recipe_autosave')]
    public function autosaveRecipe(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        if ($this->getUser()) {
            $data = $request->getPayload()->all();
            $recipeId = (int)$data['recipe_id'];
            $recipe = $entityManager->getRepository(Recipe::class)->find($recipeId);
            $field = $data['field'];
            $positionId = $data['position_id'] ?? null;
            $value = $data['value'];
            $localeCode = $data['locale_code'];

            $temp = explode('-', $field);
            $fieldType = $temp[0];
            $subField = null;
            if (!isset($temp[2])) {
                $field = $temp[1];
            } else {
                $field = $temp[2];
                $subField = $temp[1];
            }

            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if ($fieldType === 'recipe_translations') {
                $translations = $recipe->getRecipetranslations();
                foreach ($translations as $translation) {
                    if ($translation->getLocale()->getCode() === $localeCode) {

                        $elements = null;
                        switch ($subField) {
                            case 'recipe_components':
                                $elements = $translation->getComponents();
                                break;
                            case 'recipe_steps':
                                $elements = $translation->getRecipesteps();
                        }
                        if ($elements) {
                            $subClassName = str_replace(
                                ' ',
                                '',
                                ucwords(str_replace('_', ' ', substr($subField, 0, -1)))
                            );
                            $added = false;
                            $currentPosition = 0;
                            foreach ($elements as $element) {
                                if ($element->getPosition() == $positionId) {
                                    $element->$method($value);
                                    $entityManager->persist($element);
                                    $added = true;
                                }
                                $currentPosition = $element->getPosition();
                            }
                            if (!$added) {
                                $subFullClassName = 'App\Entity\\' . $subClassName;
                                $newElement = new $subFullClassName();
                                $newElement->setPosition($currentPosition + 1);
                                $newElement->$method($value);
                                $methodAdd = 'add' . $subClassName;
                                $translation->$methodAdd($newElement);
                                $entityManager->persist($translation);
                            }
                        } else {
                            $translation->$method($value);
                            $entityManager->persist($translation);
                        }
                    }
                }
            } else {
                $recipe->$method( $value);
                $entityManager->persist($recipe);
            }
            $entityManager->flush();
            return new JsonResponse(['success' => true]);
        }
    }

    #[Route('/{_locale}/add-recipe', name: 'add_recipe')]
    public function addRecipe(
        Request $request,
        RecipeRepository $recipeRepository,
        EntityManagerInterface $entityManager,
        RecipeServiceRepository $recipeServiceRepository
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $recipesIds = [1];
            $localeObject = $request->attributes->get('localeObject');
            $recipes = $recipeServiceRepository->loadItemsByIds(
                $recipesIds,
                $site->getId(),
                $localeObject->getId()
            );
            return $this->render('security/account/add-recipe.html.twig', [
                'user' => $user,
                'recipe' => $recipes[0],

            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/account-update', name: 'user_update')]
    public function accountUpdate(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        $result = [];
        if ($request->isXmlHttpRequest()) {
            if ($user = $this->getUser()) {
                $result['success'] = true;
                $formCode = $request->get('form_code');
                if ($formCode === 'user-name') {
                    $user->setFirstName($request->get('first_name') ?? '');
                    $user->setLastName($request->get('last_name') ?? '');
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $result['message'] = 'Save name success';
                }
                if ($formCode === 'avatar-image') {
                    $file = $request->files->get('image_file');
                    if (!$file) {
                        return $this->json(['error' => 'No file uploaded'], 400);
                    }

                    //  Validate mime type
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        return $this->json(['error' => 'Invalid file type'], 400);
                    }

                    //  Generate safe filename
                    $newFilename = uniqid() . '.' . $file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('app.avatar_upload_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Upload failed'], 500);
                    }
                    $avatarUrl = str_replace('/uploads/avatars/', '', $newFilename);
                    $user->setAvatarUrl($newFilename);
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $result['message'] = 'Save image success';
                    $result['avatarUrl'] = $avatarUrl;
                }
                return $this->json($result);
            }
        }
        $result['status'] = false;
        return $this->json($result);
    }

    #[Route('/{_locale}/account/recently-viewed', name: 'account_recently_viewed')]
    public function getRecentlyViewedSetting(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        RecipeViewRepository $recipeViewRepository,
    ): Response {
        if ($user = $this->getUser()) {
            $recentlyRecipesIds = $recipeViewRepository->loadRecentlyViewedRecipeIds($user->getId());
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $recentlyRecipes = $recipeServiceRepository->loadItemsByIds(
                $recentlyRecipesIds,
                $site->getId(),
                $localeObject->getId()
            );
            return $this->render('security/account/recently-viewed.html.twig', [
                'recipes' => $recentlyRecipes,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/account/setting', name: 'account_setting')]
    public function getAccountSetting(
        Request $request,
        UserRepository $userRepository
    ): Response {
        if ($user = $this->getUser()) {
            $userObject = $userRepository->find($user->getId());
            $ajaxUrl = $this->generateUrl('user_update');
            return $this->render('security/account/setting.html.twig', [
                'recipeUser' => $userObject,
                'ajaxUrl' => $ajaxUrl
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/account/my-recipes', name: 'account_my_recipes')]
    public function getMyRecipesSetting(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
    ): Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $userRecipes = $recipeServiceRepository->findByAuthorId(
                $user->getId(),
                $site->getId(),
                $localeObject->getId()
            );
            return $this->render('security/account/my-recipes.html.twig', [
                'recipes' => $userRecipes
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}
