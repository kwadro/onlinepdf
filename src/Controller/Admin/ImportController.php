<?php

namespace App\Controller\Admin;

use App\Import\ImportManager;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class ImportController extends AbstractController
{
    #[Route('/admin/import/{entity}', name: 'admin_import')]
    public function import(
        string $entity,
        Request $request,
        ImportManager $importManager
    ): Response {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');
            $result = $importManager->import($entity, $file);
            $this->addFlash(
                'success',
                sprintf(
                    'Imported: %d, created: %d, updated: %d Failed: %d',
                    $result->imported,
                    $result->created,
                    $result->updated,
                    $result->failed
                )
            );
            if(!empty($result->errors))
                $this->addFlash(
                    'danger',
                    implode("\n", $result->errors)
                );

            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/import.html.twig', [
            'entity' => $entity
        ]);
    }
}
