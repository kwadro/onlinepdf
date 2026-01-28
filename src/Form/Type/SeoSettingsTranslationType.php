<?php

namespace App\Form\Type;

use App\Entity\SeoSettingsTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\SeoSettingType;
use App\Form\Type\LocaleType;
class SeoSettingsTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seosetting', CollectionType::class, [
                'entry_type' => SeoSettingType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('locale', CollectionType::class, [
                'entry_type' => LocaleType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('meta_title', TextType::class)
            ->add('meta_description', TextareaType::class)
            ->add('meta_keywords', TextType::class)
            ->add('author', TextType::class)
            ->add('og_title', TextType::class)
            ->add('og_description', TextareaType::class)
            ->add('og_type', TextType::class)
            ->add('gtm_code', TextType::class)
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeoSettingsTranslation::class,
        ]);
    }
}