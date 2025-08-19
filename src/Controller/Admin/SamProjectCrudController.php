<?php

namespace App\Controller\Admin;

use App\Entity\SamProject;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class SamProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SamProject::class;
    }


    public function configureFields(string $pageName): iterable
    {
        $now = new \DateTimeImmutable();
        $now = $now->format('Y-m-d H:i:s');
        return [
            TextField::new('name'),
            IntegerField::new('type_server')->setRequired(false),
            TextField::new('host')->setRequired(false),
            TextField::new('port')->setRequired(false),
            TextField::new('user')->setRequired(false),
            TextField::new('password')->setRequired(false),

            DateTimeField::new('created_at')->setRequired(false)
                ->setFormTypeOptions([
                'html5' => false,
                'input' => 'datetime_immutable',
                'empty_data' => $now
            ]),
        ];
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['created_at' => 'DESC']);
    }
}
