<?php

namespace App\Form;

use App\Entity\Component;
use App\Entity\GroupComponent;
use App\Entity\Ingredient;
use App\Entity\Unit;
use App\Form\Type\CustomAddselectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupComponentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeCode = $options['locale'];
        $builder
            ->add(
                'position',
                HiddenType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control position'
                    ]
                ]
            )
            ->add('name', TextareaType::class,
                [
                    'label' => 'form.groupname',
                    'attr' => [
                        'label' => 'test',
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'form-control text-lg',
                        'maxlength' => 50,
                        'field' => 'recipe_translations-name',
                        'locale' => $localeCode,
                        'rows' => '1'
                    ]
                ]
            )
            ->add('components', CollectionType::class, [
                'label' => 'form.components',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GroupComponent::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'group_component_form',
            'locale' => null,
        ]);
    }
}
