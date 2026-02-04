<?php
namespace App\Controller\Admin;
use App\Entity\ContactForm;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactFormCrudController extends AbstractCrudController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }
    public static function getEntityFqcn(): string { return ContactForm::class; }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name')->setRequired(true),
            TextField::new('email')->setRequired(true),
            TextareaField::new('message')->setHelp('Enter full text here')->setNumOfRows('3'),
        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
         $manage = $this->translator->trans('grud.manage', [], 'messages');
         $edit = $this->translator->trans('grud.edit', [], 'messages');
         $createNew = $this->translator->trans('grud.create_new', [], 'messages');
         $linkName = $this->translator->trans('menu.link_contact_form_single', [], 'messages');
         return $crud
            ->setPageTitle('index', sprintf('%s %s',$manage,$linkName)) // For the list view
            ->setPageTitle('edit', sprintf('%s %s',$edit,$linkName).' id : %entity_id%') // For the edit form
            ->setPageTitle('new', sprintf('%s %s',$createNew,$linkName))
            ->setDefaultSort(['created_at' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_SUPER_ADMIN');
    //        ->setPermission(Action::EDIT, 'ROLE_MANAGER')
    //        ->setPermission(Action::DETAIL, 'ROLE_USER');
    }
}
