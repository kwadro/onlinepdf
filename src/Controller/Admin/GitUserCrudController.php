<?php

namespace App\Controller\Admin;

use App\Entity\GitUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class GitUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GitUser::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
