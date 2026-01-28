<?php

namespace App\Form\Type;

use App\Entity\Locale;
use App\Entity\RecipeTranslation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\LocaleType;
use App\Form\Type\ComponentType;
use App\Form\Type\RecipeStepType;
use App\Form\Type\RecipeType;
use App\Form\Type\RecipeAuthorType;
class RecipeTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', CustomLocaleType::class, [
                'class' => Locale::class,
                'choice_label' => 'name',
                'placeholder' => 'Select locale',
                'required' => true
            ])
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('is_active', ChoiceType::class,[
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
            ->add('notes', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecipeTranslation::class,
        ]);
    }
}
