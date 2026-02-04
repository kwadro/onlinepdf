<?php

namespace App\Controller\Admin;

use App\Entity\ContactForm;
use App\Entity\GitUser;
use App\Entity\SamProject;
use App\Entity\ServerData;
use App\Entity\ServerType;
use App\Entity\ServiceData;
use App\Entity\User;
use App\Entity\UserAccess;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

// @GENERATE USE START
use App\Entity\RecipeTranslation;
use App\Entity\RecipeAuthor;
use App\Entity\Recipe;
use App\Entity\RecipeCategory;
use App\Entity\RecipeStep;
use App\Entity\Ingredient;
use App\Entity\Component;
use App\Entity\Unit;
use App\Entity\Site;
use App\Entity\Locale;
use App\Entity\HeaderSetting;
use App\Entity\HeaderTranslation;
use App\Entity\SeoSetting;
use App\Entity\SeoSettingsTranslation;
use App\Entity\FooterSetting;
use App\Entity\FooterTranslation;
use App\Entity\MegaMenuSetting;
use App\Entity\MegaMenuTranslation;
use App\Entity\MegaMenuType;
// @GENERATE USE FINISH

#[AdminDashboard(routePath: '/admin/{_locale}', routeName: 'admin')]
//#[IsGranted('ROLE_SUPER_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private TranslatorInterface $translator
    ) {
    }

    public function index(): Response
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        // ...set chart data and options somehow

        return $this->render('admin/my-dashboard.html.twig', [
            'chart' => $chart
        ]);

        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->translator->trans('catalog_recipe', [], 'messages'))
            // set this option if you prefer the page content to span the entire
            // browser width, instead of the default design which sets a max width
            ->renderContentMaximized()
            //->renderSidebarMinimized()
            ->disableDarkMode()
            ->setDefaultColorScheme('dark')
            ->generateRelativeUrls()
            ->setLocales(['en','uk'])
;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard($this->translator->trans('menu.dashboard', [], 'messages'), 'fa fa-home');

        yield MenuItem::section($this->translator->trans('menu.users', [], 'messages'));
        yield MenuItem::linkToCrud($this->translator->trans('menu.users', [], 'messages'), 'fas fa-list', User::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.user_access', [], 'messages'), 'fas fa-list', UserAccess::class);
// @GENERATE MENU START
yield MenuItem::section($this->translator->trans('menu.group_catalog', [], 'messages'));
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_recipetranslation', [], 'messages'), 'fas fa-list', Recipetranslation::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_recipeauthor', [], 'messages'), 'fas fa-list', Recipeauthor::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_recipe', [], 'messages'), 'fas fa-list', Recipe::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_recipecategory', [], 'messages'), 'fas fa-list', Recipecategory::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_recipestep', [], 'messages'), 'fas fa-list', Recipestep::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_ingredient', [], 'messages'), 'fas fa-list', Ingredient::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_component', [], 'messages'), 'fas fa-list', Component::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_unit', [], 'messages'), 'fas fa-list', Unit::class);
        yield MenuItem::section($this->translator->trans('menu.group_setting', [], 'messages'));
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_site', [], 'messages'), 'fas fa-list', Site::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_locale', [], 'messages'), 'fas fa-list', Locale::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_headersetting', [], 'messages'), 'fas fa-list', Headersetting::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_headertranslation', [], 'messages'), 'fas fa-list', Headertranslation::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_seosetting', [], 'messages'), 'fas fa-list', Seosetting::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_seosettingstranslation', [], 'messages'), 'fas fa-list', Seosettingstranslation::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_footersetting', [], 'messages'), 'fas fa-list', Footersetting::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_footertranslation', [], 'messages'), 'fas fa-list', Footertranslation::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_megamenusetting', [], 'messages'), 'fas fa-list', Megamenusetting::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_megamenutranslation', [], 'messages'), 'fas fa-list', Megamenutranslation::class);
        yield MenuItem::linkToCrud($this->translator->trans('menu.link_megamenutype', [], 'messages'), 'fas fa-list', Megamenutype::class);
// @GENERATE MENU FINISH
        yield MenuItem::section($this->translator->trans('menu.contact_form', [], 'messages'));
        yield MenuItem::linkToCrud($this->translator->trans('menu.contact_form_items', [], 'messages'), 'fas fa-list', ContactForm::class);
    }
    public function configureAssets(): \EasyCorp\Bundle\EasyAdminBundle\Config\Assets
    {
        return Assets::new()
            ->addCssFile('build/admin-css.css')
            ->addCssFile('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css')
            ->addJsFile('https://cdn.jsdelivr.net/npm/flatpickr')
            ->addJsFile('lib/admin-datepicker.js');
    }
}
