<?php

namespace App\Form\Type;

use App\Entity\RecipeTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\LocaleType;
use App\Entity\Locale;
use App\Form\Type\ComponentType;
use App\Form\Type\RecipeStepType;
use App\Form\Type\RecipeType;
use App\Form\Type\UserType;
use App\Entity\User;
class RecipeTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                 ->add('locale', CustomAddselectType::class, [
                     'class' => Locale::class,
                     'choice_label' => 'name',
                     'placeholder' => 'Select locale',
                     'required' => true,
                     'attr' => [
                         'data-entity' => 'Locale',
                         'search-field'=>'code',
                         'require-fields'=>'code,name',
                     ],

                 ])
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('is_active', ChoiceType::class,[
                'choices' => [
                    'Yes' => 'Yes',
                    'No' => 'No'
                ]
            ])
            ->add('publish', ChoiceType::class,[
                'choices' => [
                    'Yes' => 'Yes',
                    'No' => 'No'
                ]
            ])
            ->add('is_popular', ChoiceType::class,[
                'choices' => [
                    'Yes' => 'Yes',
                    'No' => 'No'
                ]
            ])
            ->add('meta_title', TextType::class)
            ->add('meta_description', TextareaType::class)
            ->add('short_description', TextareaType::class)
            ->add('description', TextareaType::class)
            ->add('cuisine', TextType::class)
            ->add('notes', TextareaType::class)
            ->add('components', CollectionType::class, [
                'entry_type' => ComponentType::class,
                'entry_options' => [
                    'label' => false,
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
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
                 ->add('user', CustomAddselectType::class, [
                     'class' => User::class,
                     'choice_label' => 'name',
                     'placeholder' => 'Select user',
                     'required' => true,
                     'attr' => [
                         'data-entity' => 'User',
                         'search-field'=>'name',
                         'require-fields'=>'name',
                     ],

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
        ]);
    }
}