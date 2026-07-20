<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailMessageGroupCrudController extends AbstractCrudController
{
    public function __construct(
        private TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function detail(AdminContext $context): KeyValueStore
    {
        $emailMessage = $context->getEntity()->getInstance();
        if ($emailMessage instanceof EmailMessage && $emailMessage->getIsSeen() !== 'Yes') {
            $emailMessage->setIsSeen('Yes');
            $this->entityManager->flush();
        }

        return parent::detail($context);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $qb;
        }
        // show only inbox (sent emails show only as parent)
        $qb->andWhere('entity.mailboxtype = :mailboxtype')
            ->setParameter('mailboxtype', 'INBOX');
        $filterId = $this->resolveFilterId($request);
        $filterGroupId = $this->resolveFilterGroupId($request);

        if ($filterId > 0) {
            $qb
                ->andWhere('entity.emailfilter = :emailfilter')
                ->setParameter('emailfilter', $filterId);

            return $qb;
        }

        if ($filterGroupId > 0) {
            $qb
                ->innerJoin('entity.emailfilter', 'menu_email_filter')
                ->andWhere('menu_email_filter.filtergroup = :filtergroup')
                ->setParameter('filtergroup', $filterGroupId);
        }

        return $qb;
    }

    public static function getEntityFqcn(): string
    {
        return EmailMessage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnDetail(),
            AssociationField::new('site')->setFormTypeOption('disabled', true)->hideOnDetail(),
            TextField::new('from_address')->setRequired(true)->setFormTypeOption('disabled', true)->hideOnDetail(),
            TextField::new('from_name')->setFormTypeOption('disabled', true)->hideOnDetail(),
            TextField::new('recipient')->setFormTypeOption('disabled', true)->hideOnDetail(),
            TextField::new('subject')->hideOnDetail(),
            TextField::new('message_id'),
            TextField::new('mailboxtype')->hideOnDetail(),
            DateField::new('received_at')->renderAsNativeWidget()->hideOnDetail(),
            TextareaField::new('body_html')->setNumOfRows(12)->setFormTypeOption(
                'attr',
                ['class' => 'tinymce tinymce-email'],
            )->hideOnIndex()->hideOnDetail(),
            ChoiceField::new('is_seen')->setChoices(['Yes' => 'Yes', 'No' => 'No'])->hideOnDetail(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('subject'))
            ->add(TextFilter::new('from_name'))
            ->add(DateTimeFilter::new('received_at'));
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
                'admin/fields.html.twig',
            ])
            ->overrideTemplate('crud/detail', 'admin/email_message/detail.html.twig')
            ->overrideTemplate('crud/index', 'admin/email_message/index.html.twig')
            ->setPageTitle('index', sprintf('%s %s', $manage, $linkName))
            ->setPageTitle('edit', sprintf('%s %s', $edit, $linkName) . ' id : %entity_id%')
            ->setPageTitle('new', sprintf('%s %s', $createNew, $linkName))
            ->setDefaultSort(['created_at' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT);
    }

    private function resolveFilterId(?Request $request): int
    {
        if (!$request instanceof Request) {
            return 0;
        }

        return $request->query->getInt('filter_id');
    }

    private function resolveFilterGroupId(?Request $request): int
    {
        if (!$request instanceof Request) {
            return 0;
        }

        return $request->query->getInt('filter_group_id');
    }
}
