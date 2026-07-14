<?php

namespace App\Form\Type;

use App\Entity\Component;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\IngredientType;
use App\Entity\Ingredient;
use App\Form\Type\UnitType;
use App\Entity\Unit;
use App\Form\Type\GroupComponentType;
class ComponentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', IntegerType::class)
                 ->add('ingredient', CustomAddselectType::class, [
                     'class' => Ingredient::class,
                     'choice_label' => 'name',
                     'placeholder' => 'Select ingredient',
                     'required' => true,
                     'attr' => [
                         'data-entity' => 'Ingredient',
                         'search-field'=>'name',
                         'require-fields'=>'name,sku',
                     ],

                 ])
                 ->add('unit', CustomAddselectType::class, [
                     'class' => Unit::class,
                     'choice_label' => 'name',
                     'placeholder' => 'Select unit',
                     'required' => true,
                     'attr' => [
                         'data-entity' => 'Unit',
                         'search-field'=>'name',
                         'require-fields'=>'name',
                     ],

                 ])
            ->add('quantity', IntegerType::class)
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Component::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'component_form',
        ]);
    }
}
