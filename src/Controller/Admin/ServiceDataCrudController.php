<?php

namespace App\Controller\Admin;

use App\Entity\ServiceData;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServiceDataCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceData::class;
    }


    public function configureFields(string $pageName): iterable
    {
       $result = [
            IdField::new('id')->setRequired(false)->onlyOnIndex(),
            TextField::new('name')->setRequired(false),
            TextField::new('url')->setRequired(false),
            TextField::new('user')->setRequired(false),
            TextField::new('password')->setRequired(false),
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
            ->setPageTitle('index', 'Manage Services') // For the list view
            ->setPageTitle('edit', 'Edit Service id : %entity_id%') // For the edit form
            ->setPageTitle('new', 'Create New Service')
            ->setDefaultSort(['created_at' => 'DESC']);
    }
}
