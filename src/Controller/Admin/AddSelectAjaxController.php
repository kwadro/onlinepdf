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
class AddSelectAjaxController extends AbstractController
{
    #[Route('/admin/addselect/save', name: 'admin_addselect_submitForm')]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        if ($request->isXmlHttpRequest()) {
            $params = $request->request->all();

            $entityName = ucfirst($params['_entityName']);
            unset($params['_entityName']);

            $csrfTokenId = strtolower($entityName).'_form';
            unset($params['_csrf_token_id']);

            $token = $params['_token'];
            unset($params['_token']);

            $searchField = $params['_searchField'];
            unset($params['_searchField']);

            $requireFields = explode(',',$params['_requireFields']);
            unset($params['_requireFields']);

            if (!$csrfTokenManager->isTokenValid(new CsrfToken($csrfTokenId, $token))) {
                return $this->json([
                    'success' => false,
                    'errors' => ['_token' => ['CSRF token is invalid.']]
                ], 403);
            }

            $errors = [];
            foreach ($requireFields as $field) {
                if (!array_key_exists($field, $params) || empty($params[$field])) {
                    $errors[$field] = ucfirst($field) .' field is required';
                }
            }

            if ($errors) {
                return $this->json([
                    'errors' => $errors,
                    'success' => false
                ], 400);
            }

            $searchValue = strtolower(trim($request->request->get($searchField)));

            $entityClassName = '\\App\\Entity\\'.$entityName;
            $existing = $em->getRepository($entityClassName)
                ->findOneBy([$searchField => $searchValue]);

            if ($existing) {
                $addLocale = $existing;
            } else {
                $addLocale = new $entityClassName();
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $searchField)));
                $addLocale->$setter($searchValue);
            }

            foreach ($params as $key=>$value) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                $addLocale->$setter( $value);
            }

            $em->persist($addLocale);
            $em->flush();

            return $this->json([
                'success' => true,
                'isNew' => !$existing,
                'newItem' => [
                    'id' => $addLocale->getId(),
                    'name' => $addLocale->getName(),
                ],
            ]);
        }
        return $this->redirectToRoute('admin');
    }

    #[Route('/admin/addselect/new', name: 'admin_addselect_new')]
    public function new(Request $request): Response
    {
        $searchField = trim($request->query->get('search-field'));
        $requireFields = trim($request->query->get('require-fields'));

        $dataEntity = trim($request->query->get('data-entity'));
        $className  = '\\App\\Form\\Type\\'.$dataEntity.'Type';

        $form = $this->createForm($className);

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/locale/new_form.html.twig', [
                'form' => $form->createView(),
                'entityName' => $dataEntity,
                'requireFields' => $requireFields,
                'searchField' => $searchField,
            ]);
        }

        return $this->redirectToRoute('admin');
    }
}
