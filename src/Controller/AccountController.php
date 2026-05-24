<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Locale;
use App\Entity\Recipe;
use App\Entity\RecipeTranslation;
use App\Entity\Site;
use App\Entity\User;
use App\Form\FacebookSettingType;
use App\Form\RecipeType;
use App\Repository\FacebookSettingRepository;
use App\Repository\FacebookSettingServiceRepository;
use App\Repository\FavoriteListRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeServiceRepository;
use App\Repository\RecipeViewRepository;
use App\Repository\UserRepository;
use App\Service\GenerateFacebookPostImage;
use Doctrine\ORM\EntityManagerInterface;
use ImagickDrawException;
use ImagickException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
    #[Route('/{_locale}/{id}/publish-recipe', name: 'publish_recipe')]
    public function publishRecipe(
        Request $request,
        EntityManagerInterface $entityManager,
        $id,
        RecipeServiceRepository $recipeServiceRepository
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');

            $recipes = $recipeServiceRepository->findByRecipeId((int)$id, $site->getId(), $localeObject->getId());
            if (!$recipes) {
                return $this->redirectToRoute('account_my_recipes');
            }

            $recipe = $recipes[0];

            $recipeUserId = $recipe->getRecipetranslations()[0]->getUser()->getId();
            if ($recipeUserId === $user->getId()) {
                $newPublish = ($recipe->getRecipetranslations()[0]->getPublish() === 'Yes') ? 'No' : 'Yes';

                $recipe->getRecipetranslations()[0]->setPublish($newPublish);
                $entityManager->persist($recipe);
                $entityManager->flush();
            }

            return $this->redirectToRoute('account_my_recipes');
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    #[Route('/recipe-facebook/{id}/generate', name: 'facebook_recipe_generate')]
    public function facebookGenerate(
        Request $request,
        RecipeServiceRepository $recipeServiceRepository,
        GenerateFacebookPostImage $generateFacebookPostImage,
        $id
    ): RedirectResponse {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $recipes = $recipeServiceRepository->findByRecipeId(
                $id,
                $site->getId(),
                $localeObject->getId()
            );
            $recipe = $recipes[0];
            $imageOutput = $generateFacebookPostImage->execute($recipe, $site, $localeObject);
            if (file_exists($imageOutput)) {
                return $this->redirectToRoute('account_facebook_recipe', ['id' => $recipe->getId()]);
            } else {
            }
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/recipe-facebook/{id}/edit', name: 'account_facebook_recipe')]
    public function facebookEdit(
        Request $request,
        FacebookSettingRepository $facebookSettingRepository,
        RecipeServiceRepository $recipeServiceRepository,
        EntityManagerInterface $em,
        $id
    ): RedirectResponse|Response {

            if ($user = $this->getUser()) {
                $site = $request->attributes->get('site');
                $localeObject = $request->attributes->get('localeObject');

                $recipes = $recipeServiceRepository->findByRecipeId(
                    $id,
                    $site->getId(),
                    $localeObject->getId()
                );
                $recipe = $recipes[0];
                $facebookSetting = $facebookSettingRepository->findOneBy(
                    [
                        'recipe_id' => $id,
                        'locale' => $localeObject->getId(),
                        'site' => $site->getId()
                    ]
                );
                $formFacebook = $this->createForm(
                    FacebookSettingType::class,
                    $facebookSetting,
                    ['attr' => ['locale' => $localeObject->getCode(), 'site' => $site->getId()]]
                )->add('save', SubmitType::class, [
                    'label' => 'Зберегти'
                ])->add('save_close', SubmitType::class, [
                    'label' => 'Зберегти і закрити'
                ])->add('close', SubmitType::class, [
                    'label' => 'Закрити'
                ]);
                $formFacebook->handleRequest($request);
                if ( $formFacebook->isSubmitted()) {
                    if ($formFacebook->isValid() ) {
                        if ($formFacebook->get('close')->isClicked()) {
                            return $this->redirectToRoute('recipe_edit', ['id' => $recipe->getId()]);
                        }
                        if ($formFacebook->get('save_close')->isClicked()) {
                            $entity = $formFacebook->getData();
                            $em->persist($entity);
                            $em->flush();
                            return $this->redirectToRoute('recipe_edit', ['id' => $recipe->getId()]);
                        }
                        if ($formFacebook->get('save')->isClicked()) {
                            $entity = $formFacebook->getData();
                            $em->persist($entity);
                            $em->flush();
                            return $this->redirectToRoute('account_facebook_recipe', ['id' => $recipe->getId()]);
                        }
                    }
                }
                return $this->render('security/account/facebook-recipe.html.twig', [
                    'recipe' => $recipe,
                    'form' => $formFacebook->createView(),
                    'user' => $user,
                ]);
            }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    #[Route('/recipe-editor/{id}/edit', name: 'recipe_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        RecipeServiceRepository $recipeServiceRepository,
        GenerateFacebookPostImage $generateFacebookPostImage,
        $id
    ): Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');

            $recipes = $recipeServiceRepository->findByRecipeId(
                $id,
                $site->getId(),
                $localeObject->getId()
            );

            if (!$recipes) {
                return $this->redirectToRoute('app_login');
            }
            $recipe = $recipes[0];
            $form = $this->createForm(
                RecipeType::class,
                $recipe,
                ['attr' => ['locale' => $localeObject->getCode()]]
            )->add('save', SubmitType::class, [
                'label' => 'Зберегти'
            ])->add('save_close', SubmitType::class, [
                'label' => 'Зберегти і закрити'
            ])->add('close', SubmitType::class, [
                'label' => 'Закрити'
            ])->add('delete', SubmitType::class, [
                'label' => 'Видалити'
            ])->add('generate', SubmitType::class, [
                'label' => 'Facebook пост'
            ]);
            $form->handleRequest($request);

            $ajaxUrl = $this->generateUrl('recipe_image_update');
            if ($form->isSubmitted()) {
                if ($form->get('generate')->isClicked()) {
                    $entity = $form->getData();
                    // generate and save image to entity of recipe
                    $generateFacebookPostImage->execute($entity, $site, $localeObject);
                    return $this->redirectToRoute('account_facebook_recipe', ['id' => $entity->getId()]);
                }
                if ($form->get('delete')->isClicked()) {
                    $entity = $form->getData();
                    foreach ($entity->getRecipeTranslations() as $translation) {
                        $em->remove($translation);
                    }
                    $em->remove($entity);
                    $em->flush();
                    return $this->redirectToRoute('account_my_recipes');
                }
                if ($form->isValid()) {
                    if ($form->get('close')->isClicked()) {
                        return $this->redirectToRoute('account_my_recipes');
                    }
                    if ($form->get('save_close')->isClicked()) {
                        $entity = $form->getData();
                        $em->persist($entity);
                        $em->flush();
                        return $this->redirectToRoute(
                            'account_my_recipes',
                            [],
                            Response::HTTP_SEE_OTHER
                        );
                    }
                    if ($form->get('save')->isClicked()) {
                        $entity = $form->getData();
                        $em->persist($entity);
                        $em->flush();
                        return $this->redirectToRoute('recipe_edit', ['id' => $entity->getId()]);
                    }
                }
            } else {
                return $this->render('security/account/edit-recipe-2.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user,
                    'recipe' => $recipe,
                    'ajaxUrl' => $ajaxUrl,

                ]);
            }
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/recipe-image-update', name: 'recipe_image_update')]
    public function recipeImageUpdate(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
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
                    $recipe = $entityManager->getRepository(Recipe::class)->find($request->get('id'));
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

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    #[Route('/{_locale}/recipe/autosave', name: 'recipe_autosave')]
    public function autosaveRecipe(
        Request $request,
        EntityManagerInterface $entityManager,
        FacebookSettingServiceRepository $facebookSettingServiceRepository,
        GenerateFacebookPostImage $generateFacebookPostImage
    ) {
        if ($this->getUser()) {
            $data = $request->getPayload()->all();
            $recipeId = (int)$data['id'];
            $recipe = $entityManager->getRepository(Recipe::class)->find($recipeId);
            $field = $data['field'];
            $positionId = $data['position_id'] ?? null;
            $siteId = $data['site_id'] ?? null;
            $value = $data['value'];
            $localeCode = $data['locale_code'];
            $locale = $localeCode
                ? $entityManager->getRepository(Locale::class)->findOneBy(['code' => $localeCode])
                : null;
            $site = $siteId
                ? $entityManager->getRepository(Site::class)->find($siteId)
                : null;
            $temp = explode('-', $field);
            $fieldType = $temp[0];
            $subField = null;
            if (!isset($temp[2])) {
                $field = $temp[1];
            } else {
                $field = $temp[2];
                $subField = $temp[1];
            }
            $imageUrl = null;
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if ($fieldType === 'recipe_translations') {
                $translations = $recipe->getRecipetranslations();
                foreach ($translations as $translation) {
                    if ($translation->getLocale()->getCode() === $localeCode) {
                        $elements = null;
                        $hasSubField = false;
                        switch ($subField) {
                            case 'group_components':
                                $elements = $translation->getGroupComponents();
                                $hasSubField = true;
                                break;
                            case 'recipe_steps':
                                $hasSubField = true;
                                $elements = $translation->getRecipesteps();
                        }
                        $subClassName = '';
                        if ($hasSubField) {
                            $subClassName = str_replace(
                                ' ',
                                '',
                                ucwords(str_replace('_', ' ', substr($subField, 0, -1)))
                            );
                        }
                        if ($elements) {
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
                            if ($hasSubField) {
                                $subFullClassName = 'App\Entity\\' . $subClassName;
                                $newElement = new $subFullClassName();
                                $newElement->setPosition(1);
                                $newElement->$method($value);
                                $methodAdd = 'add' . $subClassName;
                                $translation->$methodAdd($newElement);
                                $entityManager->persist($translation);
                            } else {
                                $translation->$method($value);
                                $entityManager->persist($translation);
                            }
                        }
                    }
                }
            } elseif ($fieldType === 'facebook') {
                $facebookSetting = $facebookSettingServiceRepository
                    ->findOneBy([
                        'recipe_id' => $recipeId,
                        'site' => $site->getId(),
                        'locale' => $locale->getId()
                    ]);
                $facebookSetting->$method($value);
                $entityManager->persist($facebookSetting);
                $entityManager->flush();

                // reload facebook image
                $result = $generateFacebookPostImage->update($recipe, $locale, $site);
                if ($result['status'] === 'success') {
                    $imageUrl = $request->getSchemeAndHttpHost() . '/public/facebook/' . $result['message'];
                }
            } else {
                $recipe->$method($value);
                $entityManager->persist($recipe);
            }
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'field' => $data['field'],
                'position' => $positionId,
                'image_url' => $imageUrl,
            ]);
        }
    }

    #[Route('/{_locale}/add-recipe', name: 'add_recipe')]
    public function addRecipe(
        Request $request,
        RecipeRepository $recipeRepository,
        EntityManagerInterface $em,
        RecipeServiceRepository $recipeServiceRepository
    ): RedirectResponse|Response {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $recipe = new Recipe();
            $recipe->setSite($site);
            $recipe->setPosition(1);
            $recipe->setPrepTimeMin(10);
            $recipe->setCookTimeMin(10);
            $recipe->setServings(10);
            $recipe->setImage(null);
            $translation = new RecipeTranslation();
            $translation->setLocale($localeObject);
            $translation->setRecipe($recipe);
            $translation->setIsActive('Yes');
            $translation->setConfirmation('No');
            $translation->setPublish('No');
            $translation->setName('Новий рецепт');
            $userObject = $em->getRepository(User::class)->find($user->getId());
            $translation->setUser($userObject);
            $translation->setSlug('recipe-' . uniqid());
            $translation->setCuisine('Українська');
            $translation->setNotes('Коментар');
            $translation->setDescription('Коментар');
            $translation->setShortDescription('Короткий коментар');
            $recipe->addRecipeTranslation($translation);
            $em->persist($recipe);
            $em->persist($translation);
            $em->flush();
            $translation->setSlug('recipe-' . $recipe->getId());
            $em->persist($translation);
            $em->flush();
            return $this->redirectToRoute('recipe_edit', ['id' => $recipe->getId()]);
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
        FavoriteListRepository $favoriteListRepository
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
            $map = array_flip($recentlyRecipesIds);
            usort($recentlyRecipes, function ($a, $b) use ($map) {
                return $map[$a->getId()] <=> $map[$b->getId()];
            });

            $favoriteRecipesIds = [];
            if ($user = $this->getUser()) {
                $favoriteRecipesIds = $favoriteListRepository->loadFavoriteRecipeIds(
                    $user->getId(),
                    $site->getId(),
                    $localeObject->getId()
                );
            }

            return $this->render('security/account/recently-viewed.html.twig', [
                'recipes' => $recentlyRecipes,
                'favoriteIds' => $favoriteRecipesIds
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/account/setting', name: 'account_setting')]
    public function getAccountSetting(
        Request $request,
        UserRepository $userRepository,
        RecipeServiceRepository $recipeServiceRepository,
    ): Response {
        if ($user = $this->getUser()) {
            $userObject = $userRepository->find($user->getId());
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $userRecipes = $recipeServiceRepository->findByAuthorId(
                $user->getId(),
                $site->getId(),
                $localeObject->getId()
            ) ?? [];
            $ajaxUrl = $this->generateUrl('user_update');
            return $this->render('security/account/setting.html.twig', [
                'countRecipe' => count($userRecipes),
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
