<?php

namespace App\Controller\Admin;

use App\Entity\Locale;
use App\Form\Type\LocaleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class LocaleAjaxController extends AbstractController
{
    #[Route('/admin/locale/save', name: 'admin_locale_submitForm')]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        if ($request->isXmlHttpRequest()) {
            $code = strtolower(trim($request->request->get('code')));
            $name = trim($request->request->get('name'));
            $isDefault = trim($request->request->get('is_default'));
            $token = trim($request->request->get('_token'));
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('locale_form', $token))) {
                return $this->json([
                    'success' => false,
                    'errors' => ['_token' => ['CSRF token is invalid.']]
                ], 403);
            }
            $errors = [];
            if (!$name) {
                $errors['name'] = 'Name field is required';
            }
            if (!$code) {
                $errors['code'] = 'Code field is required';
            }
            if ($errors) {
                return $this->json([
                    'errors' => $errors,
                    'success' => false
                ], 400);
            }

            $existing = $em->getRepository(Locale::class)
                ->findOneBy(['code' => $code]);
            $addLocale = null;
            if ($existing) {
                $existing->setName($name);
                $existing->setIsDefault($isDefault);
                $em->persist($existing);
                $em->flush();
                $addLocale = $existing;
            } else {
                $newLocale = new Locale();
                $newLocale->setName($name);
                $newLocale->setIsDefault($isDefault);
                $newLocale->setCode($code);
                $em->persist($newLocale);
                $em->flush();
                $addLocale = $newLocale;
            }
            return $this->json([
                'success' => true,
                'newLocale' => [
                    'id' => $addLocale->getId(),
                    'name' => $addLocale->getName()
                ],
            ]);
        }
        return $this->redirectToRoute('admin');
    }

    #[Route('/admin/locale/new', name: 'admin_locale_new')]
    public function new(Request $request): Response
    {
        $form = $this->createForm(LocaleType::class);
        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/locale/new_form.html.twig', [
                'form' => $form->createView(),
            ]);
        }
        return $this->redirectToRoute('admin');
    }
}
