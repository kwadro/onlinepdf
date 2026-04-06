<?php

namespace App\Form;

use App\Entity\RecipeStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeCode = $options['locale'];
        $builder
            ->add('drag', ButtonType::class, [
                'label' => '☰',
                'attr' => [
                    'class' => 'drag-handle'
                ]
            ])
            ->add(
                'position',
                HiddenType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control position'
                    ]
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control name',
                        'data-controller' => 'text',
                        'data-text-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'field' => 'recipe_translations-recipe_steps-name',
                        'locale' => $localeCode
                    ]
                ]
            )
            ->add(
                'answer',
                TextareaType::class,
                [
                    'label' => 'Description',
                    'attr' => [
                        'data-controller' => 'smarttextarea',
                        'data-smarttextarea-autosave-url-value' => '/' . $localeCode . '/recipe/autosave',
                        'class' => 'mb-1 form-control text-sm answer',
                        'maxlength' => 1000,
                        'field' => 'recipe_translations-recipe_steps-answer',
                        'locale' => $localeCode
                    ]
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeStep::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'recipestep_form',
            'locale' => null
        ]);
    }
}
