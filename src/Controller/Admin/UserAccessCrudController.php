<?php

namespace App\Controller\Admin;

use App\Entity\UserAccess;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

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
                ->autocomplete()->onlyOnIndex(),
            AssociationField::new('project', 'Project')
                ->setCrudController(SamProjectCrudController::class)
                ->setFormTypeOption('by_reference', true)
                ->autocomplete(),
            ChoiceField::new('servertype','Server Type')
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
                ]),
            BooleanField::new('service','Service')
        ];
    }
}
