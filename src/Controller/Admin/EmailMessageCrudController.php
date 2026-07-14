<?php
namespace App\Controller\Admin;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use App\Entity\EmailMessage;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Contracts\Translation\TranslatorInterface;



class EmailMessageCrudController extends AbstractCrudController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }
    public static function getEntityFqcn(): string { return EmailMessage::class; }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('site'),
            AssociationField::new('mailbox'),
            AssociationField::new('sender_filter'),
            IntegerField::new('imap_uid')
                ->setFormTypeOption('attr', ['min' => 0, 'max' => 1000])
                ->setHelp('Enter a positive number only')->setRequired(true),
            TextField::new('message_id')->hideOnIndex(),
            TextField::new('from_address')->setRequired(true),
            TextField::new('from_name'),
            TextField::new('recipient'),
            TextField::new('subject'),
        
        
            TextareaField::new('body_html')->setNumOfRows(12)->setFormTypeOption('attr', ['class' => 'tinymce tinymce-email'])->hideOnIndex(),
            DateField::new('received_at')->renderAsNativeWidget(),
            ChoiceField::new('is_seen')->setChoices(['Yes' => 'Yes', 'No' => 'No']),
        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
         $manage = $this->translator->trans('grud.manage', [], 'messages');
         $edit = $this->translator->trans('grud.edit', [], 'messages');
         $createNew = $this->translator->trans('grud.create_new', [], 'messages');
         $linkName = $this->translator->trans('menu.link_emailmessage_single', [], 'messages');
         return $crud
            ->setFormThemes([
               '@EasyAdmin/crud/form_theme.html.twig',
               'admin/fields.html.twig'
            ])
            ->setPageTitle('index', sprintf('%s %s',$manage,$linkName)) // For the list view
            ->setPageTitle('edit', sprintf('%s %s',$edit,$linkName).' id : %entity_id%') // For the edit form
            ->setPageTitle('new', sprintf('%s %s',$createNew,$linkName))
            ->setDefaultSort(['created_at' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
          return $actions
             ->disable(Action::NEW, Action::DELETE);
    //    return $actions
    //        ->setPermission(Action::DETAIL, 'ROLE_USER');
    }
}
