<?php

namespace App\Form\Type;

use App\Entity\FacebookSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\SiteType;
use App\Form\Type\LocaleType;
use App\Entity\Locale;
class FacebookSettingType extends AbstractType
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
            ->add('recipe_id', IntegerType::class)
            ->add('text_post', TextareaType::class)
            ->add('tag', TextType::class)
            ->add('title1', TextType::class)
            ->add('title2', TextType::class)
            ->add('title3', TextType::class)
            ->add('content1', TextType::class)
            ->add('content2', TextType::class)
            ->add('content3', TextType::class)
            ->add('content4', TextType::class)
            ->add('notes', TextType::class)
            ->add('template', IntegerType::class)
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacebookSetting::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'facebooksetting_form',
        ]);
    }
}
