<?php

namespace App\Form\Type;

use App\Entity\EmailMailboxSetting;
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
use App\Form\Type\EmailMessageType;
class EmailMailboxSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boxname', TextType::class)
            ->add('boxhost', TextType::class)
            ->add('boxport', IntegerType::class)
            ->add('boxusername', TextType::class)
            ->add('boxpassword', TextType::class)
            ->add('boxencryption', TextType::class)
            ->add('boxmailbox', TextType::class)
            ->add('boxactive', ChoiceType::class,[
                'choices' => [
                    'Yes' => 'Yes',
                    'No' => 'No'
                ]
            ])
            ->add('boxlast_uid', IntegerType::class)
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailMailboxSetting::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'emailmailboxsetting_form',
        ]);
    }
}
