<?php

namespace App\Service;

use App\Repository\FooterSettingRepository;
use App\Repository\HeaderSettingRepository;
use App\Repository\LocaleRepository;
use App\Repository\SeoSettingRepository;
use App\Repository\SiteRepository;

class SiteSettingsProvider
{
    public function __construct(
        private SiteRepository $siteRepo,
        private SeoSettingRepository $seoRepo,
        private HeaderSettingRepository $headerRepo,
        private FooterSettingRepository $footerRepo,
        private LocaleRepository $localeRepo,
    ) {}

    public function getSettings(string $domain, string $locale): array
    {
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $locale]);
        $seoSetting = $this->seoRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $headerSetting = $this->headerRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];
        $footerSetting = $this->footerRepo->findOneBySiteAndLocale($site->getId(), $localeObject->getId()) ?? [];

        return [
            'seo' => $seoSetting ? [
                'id' => $seoSetting->getId(),
                'meta_title' =>  $seoSetting->getTranslations()[0]->getMetaTitle(),
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
        ];
    }

}
