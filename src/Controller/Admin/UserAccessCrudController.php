<?php

namespace App\Controller\Admin;

use App\Entity\UserAccess;
use App\Form\Type\ServerDataType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserAccessCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserAccess::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user')
                ->setCrudController(UserCrudController::class)
                ->setFormTypeOption('by_reference', true)
                ->autocomplete(),
            ChoiceField::new('servertype')
                ->setChoices([
                    'Live' => 1,
                    'Test' => 2,
                    'Stage' => 3,
                ])
                ->allowMultipleChoices()
                ->autocomplete()
                ->renderAsBadges()
                ->setFormTypeOptions([
                    'expanded' => false,
                    'multiple' => true,
                    'required' => true,
                ])
        ];
    }
}
