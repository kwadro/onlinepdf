<?php

namespace App\Form;

use App\Entity\FacebookSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacebookSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeCode = $options['attr']['locale'];
        $site = $options['attr']['site'];

        $builder
            ->add('id', HiddenType::class)
            ->add('recipe_id', HiddenType::class)
            ->add(
                'text_post',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-lg field-text-post',
                        'maxlength' => 3000,
                        'field' => 'facebook-text_post',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '10'
                    ]
                ]
            )
            ->add(
                'tag',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control field-tag',
                        'maxlength' => 100,
                        'field' => 'facebook-tag',
                        'locale' => $localeCode,
                        'site' => $site,
                    ]
                ]
            )
            ->add(
                'title1',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-title',
                        'maxlength' => 50,
                        'field' => 'facebook-title1',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'title2',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-title',
                        'maxlength' => 50,
                        'field' => 'facebook-title2',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'title3',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-title',
                        'maxlength' => 50,
                        'field' => 'facebook-title3',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'content1',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-content',
                        'maxlength' => 100,
                        'field' => 'facebook-content1',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'content2',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-content',
                        'maxlength' => 100,
                        'field' => 'facebook-content2',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'content3',
                TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-content',
                        'maxlength' => 100,
                        'field' => 'facebook-content3',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add(
                'notes',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-sm field-notes',
                        'maxlength' => 100,
                        'field' => 'facebook-notes',
                        'locale' => $localeCode,
                        'site' => $site,
                        'rows' => '1'
                    ]
                ]
            )
            ->add('template', HiddenType::class, [
                'required' => false,
                'empty_data' => '1',
                'attr' => [
                    'data-facebook-recipe-target' => 'templateInput',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacebookSetting::class,
            'csrf_protection' => true,
            'locale' => null,
        ]);
    }
}
