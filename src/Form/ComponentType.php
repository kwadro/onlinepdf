<?php

namespace App\Form;

use App\Entity\Component;
use App\Entity\Ingredient;
use App\Entity\Unit;
use App\Form\Type\CustomAddselectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComponentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeCode = $options['locale'];

        $builder
            ->add('drag', ButtonType::class, [
                'label' => '☰',
                'attr' => [
                    'class' => 'drag-handle component'
                ]
            ])
            ->add('name', HiddenType::class, [
                'attr' => [
                    'class' => 'mb-1 form-control name'
                ]
            ])
            ->add('ingredient', CustomAddselectType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'placeholder' => 'Select ingredient',
                'required' => true,
                'attr' => [
                    'data-entity' => 'Ingredient',
                    'search-field' => 'name',
                    'require-fields' => 'name,sku',
                    'class' => 'mb-1 form-control ingredient'
                ],

            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'class' => 'mb-1 form-control quantity'
                ]
            ])
            ->add('unit', CustomAddselectType::class, [
                'class' => Unit::class,
                'choice_label' => 'name',
                'placeholder' => 'Select unit',
                'required' => true,
                'attr' => [
                    'data-entity' => 'Unit',
                    'search-field' => 'name',
                    'require-fields' => 'name',
                    'class' => 'mb-1 form-control unit'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Component::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'component_form',
            'locale' => null,
        ]);
    }
}
