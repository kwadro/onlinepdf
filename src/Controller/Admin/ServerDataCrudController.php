<?php

namespace App\Controller\Admin;

use App\Entity\ServerData;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Validator\Constraints\File;

class ServerDataCrudController extends AbstractCrudController
{
    private UploaderHelper $uploaderHelper;

    public function __construct(
        UploaderHelper $uploaderHelper
    ) {
        $this->uploaderHelper = $uploaderHelper;
    }

    public static function getEntityFqcn(): string
    {
        return ServerData::class;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleFileUpload($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleFileUpload($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function handleFileUpload($entityInstance): void
    {
        $request = $this->getContext()->getRequest();
        $uploadedFile = $request->files->get('ServerData')['dump_link_upload'];
        if ($uploadedFile instanceof UploadedFile) {
            try {
                $newFilename = $this->uploaderHelper->uploadServerFile($uploadedFile);
                $this->uploaderHelper->setFileNameToEntity($newFilename, $entityInstance);
            } catch (FileException $e) {
                echo "Error: " . $e->getMessage();
                exit;
            }
        }
    }

    /**
     * @param string $pageName
     * @return iterable
     */
    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()?->getEntity()?->getInstance();

        $oldFileName = null;
        $oldFilePath = null;
        if ($entity instanceof ServerData) {
            $oldFileName = $entity->getDumpLink()
                ? basename($entity->getDumpLink())
                : null;
            $oldFilePath = $entity->getDumpLink() ?? null;
        }

        $help = ($oldFileName && $oldFilePath)
            ? sprintf("Current file: <a href='%s'>%s</a>", $oldFilePath, $oldFileName)
            : "No file uploaded yet";

        $result = [
            IdField::new('id')->setRequired(false)->onlyOnIndex(),
            ChoiceField::new('type_server','Type Server')
                ->setChoices([
                    'Live' => 1,
                    'Test' => 2,
                    'Stage' => 3,
                ])
                ->setFormTypeOptions([
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
            ])->setRequired(true),
            TextField::new('hostname')->setRequired(false),
            TextField::new('port')->setRequired(false),
            TextField::new('username')->setRequired(false),
            TextField::new('password')->setRequired(false),
            UrlField::new('dump_link')->setFormTypeOptions([
                'mapped' => true,
                'disabled' => true
            ])->formatValue(function ($value) {
                return basename($value);
            }),
            TextField::new('dump_link_upload')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['accept' => '.zip'],
                    'constraints' => [
                        new File([
                            'maxSize' => '10M',
                            'mimeTypes' => [
                                'application/zip',
                                'application/x-zip-compressed',
                                'multipart/x-zip',
                            ],
                            'mimeTypesMessage' => 'Please upload a valid ZIP file',
                        ]),
                    ],
                ])
                ->setLabel('Upload Dump (zip)')->onlyOnForms()
                ->setHelp($help),

            DateTimeField::new('created_at')->onlyOnIndex(),
            DateTimeField::new('updated_at')->onlyOnIndex(),

            AssociationField::new('project')
                ->setCrudController(SamProjectCrudController::class)
                ->setFormTypeOption('by_reference', true)
                ->autocomplete(),
        ];

        return $result;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Manage Servers') // For the list view
            ->setPageTitle('edit', 'Edit Server id : %entity_id%') // For the edit form
            ->setPageTitle('new', 'Create New Server')
            ->setDefaultSort(['created_at' => 'DESC']);
    }
}
