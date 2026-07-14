<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Kwadro\UserSubscription\Entity\SubscriptionPlan;
use Kwadro\UserSubscription\Enum\BillingInterval;

class SubscriptionPlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubscriptionPlan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('code'),
            TextField::new('name'),
            IntegerField::new('price')->setHelp('Price in minor units (e.g. kopecks/cents).'),
            TextField::new('currency'),
            ChoiceField::new('interval')->setChoices([
                'Monthly' => BillingInterval::Monthly,
                'Yearly' => BillingInterval::Yearly,
            ]),
            ArrayField::new('features'),
            BooleanField::new('active'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Subscription Plan')
            ->setEntityLabelInPlural('Subscription Plans');
    }
}
