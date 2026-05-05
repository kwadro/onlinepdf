<?php

namespace App\Controller\Admin;

use App\Entity\ServerData;
use App\Entity\User;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
       // $this->handleFileUpload($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }



    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        //$this->handleFileUpload($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()?->getEntity()?->getInstance();

        return [
            IdField::new('id')->onlyOnForms(),
            BooleanField::new('isVerified')->setRequired(false),
            TextField::new('first_name')->setLabel('First Name'),
            TextField::new('last_name')->setLabel('Last Name'),
            EmailField::new('email'),
            ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Editor' => 'ROLE_EDITOR',
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderAsBadges(),
            CollectionField::new('accesses')
                ->useEntryCrudForm(UserAccessCrudController::class)
                ->setEntryIsComplex(true)
                ->allowAdd()
                ->allowDelete()
                ->renderExpanded(false)
                ->setFormTypeOption('by_reference', false)
                ->onlyOnForms(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Manage Users')
            ->setPageTitle('edit', 'Edit Users id : %entity_id%')
            ->setPageTitle('new', 'Create New Users')
            ->setDefaultSort(['created_at' => 'DESC']);
    }
}
