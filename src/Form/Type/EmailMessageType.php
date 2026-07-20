<?php

namespace App\Form\Type;

use App\Entity\EmailMessage;
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
use App\Form\Type\EmailMailboxType;
use App\Form\Type\EmailMailboxFolderType;
use App\Form\Type\EmailFilterType;
class EmailMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailboxtype', ChoiceType::class, [
                'choices' => [
                    'INBOX' => 'inbox',
                    'SENT' => 'sent',
                ],
            ])
            ->add('imap_uid', IntegerType::class)
            ->add('message_id', TextType::class)
            ->add('parent_message_id', TextType::class)
            ->add('in_reply_to', TextType::class)
            ->add('mailreferences', TextType::class)
            ->add('from_address', TextType::class)
            ->add('from_name', TextType::class)
            ->add('recipient', TextType::class)
            ->add('subject', TextType::class)
            
            ->add('body_html', TextareaType::class, [
                'attr' => ['class' => 'tinymce tinymce-email'],
            ])
            ->add('is_seen', ChoiceType::class,[
                'choices' => [
                    'Yes' => 'Yes',
                    'No' => 'No'
                ]
            ])
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailMessage::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'emailmessage_form',
        ]);
    }
}
