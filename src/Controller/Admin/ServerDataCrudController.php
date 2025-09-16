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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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

    private function handleFileUpload($entityInstance): void
    {
        $request = $this->getContext()->getRequest();
        $uploadedFile = $request->files->get('ServerData')['dump_link_upload'];
        if ($uploadedFile instanceof UploadedFile) {
            try {
               $this->uploaderHelper->uploadServerFile($uploadedFile, $entityInstance);
            } catch (FileException $e) {
                echo "Error: " . $e->getMessage();
                exit;
            }
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleFileUpload($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
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
            FormField::addTab('SSH Connect'),
            AssociationField::new('project')
                ->setCrudController(SamProjectCrudController::class)
                ->setFormTypeOption('by_reference', true)
                ->autocomplete()->onlyOnIndex(),
            ChoiceField::new('type_server', 'Type Server')
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

            FormField::addTab('File Collection'),
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

            DateTimeField::new('created_at')->hideOnForm(),
            DateTimeField::new('updated_at')->hideOnForm(),

            FormField::addTab('WEB'),
            FormField::addFieldset('Environment'),
            TextField::new('php_version')->setRequired(false)->hideOnIndex(),
            TextField::new('framework_version')->setRequired(false)->hideOnIndex(),
            FormField::addFieldset('Url'),
            TextField::new('web_url')->setRequired(false)->hideOnIndex(),
            TextField::new('web_admin_url')->setRequired(false)->hideOnIndex(),
            TextField::new('web_admin_login','Admin Login')->setRequired(false)->hideOnIndex(),
            TextField::new('web_admin_password','Admin Password')->setRequired(false)->hideOnIndex(),
            FormField::addFieldset('Http Authorization'),
            TextField::new('http_auth_login','Http Login')->setRequired(false)->hideOnIndex(),
            TextField::new('http_auth_password','Http Password')->setRequired(false)->hideOnIndex(),

            FormField::addTab('Project Association'),
            AssociationField::new('project')
                ->setCrudController(SamProjectCrudController::class)
                ->setFormTypeOption('by_reference', true)
                ->autocomplete()->hideOnIndex(),
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
