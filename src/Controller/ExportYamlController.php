<?php

namespace App\Controller;

use App\Service\YamlEntityExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;


class ExportYamlController extends AbstractController
{
    public function __construct(
        private readonly YamlEntityExporter $exporter
    ) {

    }
    #[Route('admin/exportyaml/{entity}', name: 'admin_export_yaml')]
    public function export(
        string $entity
    ): BinaryFileResponse {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException('Entity not found');
        }
        $projectDir = $this->getParameter('kernel.project_dir');
        $fileName = strtolower($entity) . '.yaml';
        $filePath = $projectDir.'/export-yaml/' . $fileName;
        $this->exporter->export($entityClass, $filePath);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Export file not found');
        }

        return $this->file(
            $filePath,
            $fileName
        );
    }
}
