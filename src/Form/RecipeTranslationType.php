<?php

namespace App\Form;

use App\Entity\Locale;
use App\Entity\RecipeTranslation;
use App\Entity\User;
use App\Form\Type\CustomAddselectType;
use App\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeCode = $options['locale'];

        $builder
            ->add('name', TextareaType::class,
                ['attr' => [
                    'data-controller' => 'smarttextarea',
                    'data-smarttextarea-autosave-url-value' => '/'.$localeCode.'/recipe/autosave',
                    'class' => 'form-control text-lg',
                    'maxlength' => 50,
                    'field' => 'recipe_translations-name',
                    'locale' => $localeCode,
                    'rows' => '1'
                ]]
            )
            ->add(
                'short_description', TextareaType::class,
                ['attr' => [
                    'data-controller' => 'smarttextarea',
                    'data-smarttextarea-autosave-url-value' => '/'.$localeCode.'/recipe/autosave',
                    'class' => 'form-control text-sm',
                    'maxlength' => 150,
                    'field' => 'recipe_translations-short_description',
                    'locale' => $localeCode,
                    'rows' => '1'
                ]]
            )
            ->add(
                'description', TextareaType::class,
                ['attr' => [
                    'data-controller' => 'smarttextarea',
                    'data-smarttextarea-autosave-url-value' => '/'.$localeCode.'/recipe/autosave',
                    'class' => 'form-control text-sm',
                    'maxlength' => 350,
                    'field' => 'recipe_translations-description',
                    'locale' => $localeCode,
                    'rows' => '1'
                ]]
            )

            ->add('notes', TextareaType::class,
                ['attr' => [
                    'data-controller' => 'smarttextarea',
                    'data-smarttextarea-autosave-url-value' => '/'.$localeCode.'/recipe/autosave',
                    'class' => 'form-control text-sm',
                    'maxlength' => 1000,
                    'field' => 'recipe_translations-notes',
                    'locale' => $localeCode,
                    'rows' => '1'
                ]]
            )
            ->add('components', CollectionType::class, [
                'entry_type' => ComponentType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'collection recipe-component-item'
                    ],
                    'locale' => $localeCode
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('recipesteps', CollectionType::class, [
                'entry_type' => RecipeStepType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'collection recipe-step-item'
                    ],
                    'locale' => $localeCode
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeTranslation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'recipetranslation_form',
            'locale' => null,
        ]);
    }
}
