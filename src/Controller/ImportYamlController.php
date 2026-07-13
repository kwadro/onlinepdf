<?php

namespace App\Controller;

use App\Service\YamlEntityImporter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ImportYamlController extends AbstractController
{
    #[Route('admin/importyaml/{entity}', name: 'admin_import_yaml')]
    public function import(
        string $entity,
        Request $request,
        YamlEntityImporter $importer,
        EntityManagerInterface $entityManager,
    ) {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');
            $result = $importer->import($entityManager,$entity, $file);
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
        return $this->render('admin/import-yaml.html.twig', [
            'entity' => $entity
        ]);
    }
}
