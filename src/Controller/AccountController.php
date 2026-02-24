<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RecipeServiceRepository;
use App\Repository\RecipeViewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AccountController extends AbstractController
{
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
                if($formCode==='user-name'){
                    $user->setFirstName($request->get('first_name') ?? '');
                    $user->setLastName($request->get('last_name') ?? '');
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $result['message'] = 'Save name success';
                }
                if($formCode === 'user-image'){
                    $file =  $request->files->get('avatar_file');
                    if (!$file) {
                        return $this->json(['error' => 'No file uploaded'], 400);
                    }

                    //  Validate mime type
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        return $this->json(['error' => 'Invalid file type'], 400);
                    }

                    //  Generate safe filename
                    $newFilename = uniqid().'.'.$file->guessExtension();
                    try {
                        $file->move(
                            $this->getParameter('app.avatar_upload_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Upload failed'], 500);
                    }
                    $avatarUrl = str_replace('/uploads/avatars/','',$newFilename);
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
