<?php

namespace App\Form\Type;


use App\Entity\SeoSettingsTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SeoSettingsTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('meta_title', TextType::class)
            ->add('meta_keywords', TextType::class)
            ->add('author', TextType::class)
            ->add('og_title', TextType::class)
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