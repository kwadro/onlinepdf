<?php

namespace App\Controller\Admin;

use App\Entity\SamProject;
use App\Form\ServerDataFormType;
use App\Form\Type\ServerDataType;
use App\Repository\SamProjectRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SamProjectCrudController extends AbstractCrudController
{
    private UploaderHelper $uploaderHelper;

    public function __construct(
        UploaderHelper $uploaderHelper
    ) {
        $this->uploaderHelper = $uploaderHelper;
    }

    public static function getEntityFqcn(): string
    {
        return SamProject::class;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleFileUpload($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    private function handleFileUpload($entityInstance): void
    {
        $request = $this->getContext()->getRequest();
        $serversItems = $request->files->get('SamProject')['servers'];
        $newFilenames = [];
        foreach ($serversItems as $key => $serversItem) {
            $uploadedFile = $serversItem['dump_link_upload'];
            if ($uploadedFile instanceof UploadedFile) {
                try {
                    $newFilenames[$key] = $this->uploaderHelper->uploadServerFile($uploadedFile);
                } catch (FileException $e) {
                    echo "Error: " . $e->getMessage();
                    exit;
                }
            }
        }
        $servers = $entityInstance->getServers();
        foreach ($servers as $key => $server) {
            if (isset($newFilenames[$key])) {
                $this->uploaderHelper->setFileNameToEntity($newFilenames[$key], $server);
            }
        }
    }
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleFileUpload($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name'),
            TextField::new('description')->setRequired(false),
            DateTimeField::new('created_at')->onlyOnIndex(),
            DateTimeField::new('updated_at')->onlyOnIndex(),
            CollectionField::new('servers')
                ->setEntryType(ServerDataType::class)
                ->setEntryIsComplex(true)
                ->allowAdd()
                ->allowDelete()
                ->renderExpanded()
                ->setFormTypeOption('by_reference', false),
        ];
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Manage Projects')
            ->setPageTitle('edit', 'Edit Project id : %entity_id%')
            ->setPageTitle('new', 'Create New Project')
            ->setDefaultSort(['created_at' => 'DESC']);
    }
    public function configureActions(Actions $actions): Actions
    {
        $exportAll = Action::new('exportCsvAll', 'Export CSV (All Filtered)')
            ->linkToCrudAction('exportCsvAllAction')
            ->createAsGlobalAction();
        return $actions
            ->add(Crud::PAGE_INDEX, $exportAll);


    }
    public function exportCsvAllAction(AdminContext $context, SamProjectRepository $entityRepository): Response
    {
        $entities = $entityRepository->createQueryBuilder('e')
            ->getQuery()
            ->getResult();
        return $this->createCsvResponse($entities, 'projects_all.csv');
    }
    private function getExportFields(): array
    {
        return [
            'id'        => 'ID',
            'Name'      => 'Project Name',
            'Description'      => 'Project Description',
            'RepositoryUrl' => 'Created At',
            'RepositoryUser' => 'Repository User',
            'RepositoryPassword' => 'Repository Password',
            'CreatedAt' => 'Created At'
        ];
    }


    private function createCsvResponse(iterable $entities, string $filename): Response
    {
        $fields = $this->getExportFields();

        $response = new StreamedResponse(function () use ($entities, $fields) {
            $handle = fopen('php://output', 'w+');

            // Titles
            fputcsv($handle, array_values($fields));

            // Data Rows
            foreach ($entities as $entity) {
                $row = [];
                foreach (array_keys($fields) as $prop) {
                    $getter = 'get' . ucfirst($prop);
                    $value = method_exists($entity, $getter) ? $entity->$getter() : null;

                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    $row[] = is_scalar($value) ? $value : (string) $value;
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $filename)
        );

        return $response;
    }
}
