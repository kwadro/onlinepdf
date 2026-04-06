<?php
// src/Form/RecipeType.php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('id', HiddenType::class)

            ->add(
                'prep_time_min',
                IntegerType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control prep_time_min'
                    ]
                ]
            )
            ->add(
                'cook_time_min',
                IntegerType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control cook_time_min'
                    ]
                ]
            )
            ->add(
                'servings',
                IntegerType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control servings'
                    ]
                ]
            )
            ->add('recipetranslations', CollectionType::class, [
                'entry_type' => RecipeTranslationType::class,
                'by_reference' => false,
                'allow_add' => false,
                'entry_options' => [
                    'locale' => $options['attr']['locale'],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
