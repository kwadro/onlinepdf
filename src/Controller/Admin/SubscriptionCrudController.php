<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Enum\SubscriptionStatus;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user'),
            AssociationField::new('plan'),
            ChoiceField::new('status')->setChoices([
                'Pending' => SubscriptionStatus::Pending,
                'Active' => SubscriptionStatus::Active,
                'Cancelled' => SubscriptionStatus::Cancelled,
                'Expired' => SubscriptionStatus::Expired,
            ]),
            DateTimeField::new('startedAt'),
            DateTimeField::new('expiresAt'),
            DateTimeField::new('cancelledAt'),
            DateTimeField::new('renewedAt'),
            TextField::new('externalId'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User Subscription')
            ->setEntityLabelInPlural('User Subscriptions');
    }
}
