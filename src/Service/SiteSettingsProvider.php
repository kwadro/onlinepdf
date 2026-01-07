<?php

namespace App\Service;

use App\Repository\FooterSettingRepository;
use App\Repository\HeaderSettingRepository;
use App\Repository\LocaleRepository;
use App\Repository\MegaMenuSettingRepository;
use App\Repository\SeoSettingRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;

class SiteSettingsProvider
{
    public function __construct(
        private SiteRepository $siteRepo,
        private SeoSettingRepository $seoRepo,
        private HeaderSettingRepository $headerRepo,
        private FooterSettingRepository $footerRepo,
        private MegaMenuSettingRepository $menuRepo,
        private LocaleRepository $localeRepo,
        private EntityManagerInterface $em
    ) {
    }

    public function getSettings(string $domain, string $locale): array
    {
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $locale]);
        if (!$site || !$localeObject) {
            return [];
        }
        $seoSetting = $this->seoRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $headerSetting = $this->headerRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $footerSetting = $this->footerRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $menuSettingRes = $this->menuRepo->findBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $menuSetting = [];
        $menuUrlKey = '';
        foreach ($menuSettingRes[0]->getTranslations() as $menuSettingItem) {
            $content = $menuSettingItem->getContent();
            if ($menuSettingItem->getMegamenutype() == 'Collection') {
                $menuUrlKey = $menuSettingItem->getUrl();
                $content = explode('"', $content)[1];
                $entityClass = 'App\\Entity\\' . $content;
                $repo = $this->em->getRepository($entityClass);
                $defaultCategory = $repo->findDefaultItem();
                $content = [];
                foreach ($defaultCategory->getChildren() as $child) {
                    $childrenCategories = [];
                    if ($child->getChildren()->count() > 0) {
                        foreach ($child->getChildren() as $child2) {
                            $children2Categories = [];
                            if ($child2->getChildren()->count() > 0) {
                                foreach ($child2->getChildren() as $child3) {
                                    $children2Categories[] = [
                                        'name' => $child3->getName(),
                                        'slug' => $child3->getSlug()
                                    ];
                                }
                            }
                            $childrenCategories[] = [
                                'name' => $child2->getName(),
                                'slug' => $child2->getSlug(),
                                'children' => $children2Categories
                            ];
                        }
                    }
                    $content[] = [
                        'name' => $child->getName(),
                        'slug' => $child->getSlug(),
                        'children' => $childrenCategories
                    ];
                }
            }
            $menuSetting[] = [
                'id' => $menuSettingItem->getId(),
                'content' => $content,
                'name' => $menuSettingItem->getName(),
                'megamenutype' => $menuSettingItem->getMegamenutype(),
                'position' => $menuSettingItem->getPosition(),
                'url' => $menuSettingItem->getUrl()
            ];
        }
        return [
            'seo' => $seoSetting ? [
                'id' => $seoSetting->getTranslations()[0]->getId(),
                'meta_title' => $seoSetting->getTranslations()[0]->getMetaTitle(),
                'meta_description' => $seoSetting->getTranslations()[0]->getMetaDescription(),
                'meta_keywords' => $seoSetting->getTranslations()[0]->getMetaKeywords(),
                'author' => $seoSetting->getTranslations()[0]->getAuthor(),
                'og_title' => $seoSetting->getTranslations()[0]->getOgTitle(),
                'og_description' => $seoSetting->getTranslations()[0]->getOgDescription(),
                'og_image' => $seoSetting->getTranslations()[0]->getOgImage(),
                'og_type' => $seoSetting->getTranslations()[0]->getOgType(),
                'gtm_code' => $seoSetting->getTranslations()[0]->getGtmCode(),
            ] : [],
            'header' => $headerSetting ? [
                'id' => $headerSetting->getId(),
                'favicon' => $headerSetting->getFavicon(),
                'logo' => $headerSetting->getLogo(),
                'title' => $headerSetting->getTranslations()[0]->getTitle()
            ] : [],
            'footer' => $footerSetting ? [
                'id' => $footerSetting->getId(),
                'content' => $footerSetting->getTranslations()[0]->getContent()
            ] : [],
            'menu_url_key'=>$menuUrlKey,
            'menu' => $menuSetting ?: [],
        ];
    }

}
