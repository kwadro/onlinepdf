<?php

namespace App\Form\Type;

use App\Entity\EmailSenderFilter;
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
use App\Form\Type\EmailSenderFilterGroupType;
use App\Form\Type\EmailMessageType;
class EmailSenderFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filtername', TextType::class)
            ->add('filtersender', TextType::class)
            ->add('match_mode', ChoiceType::class, [
                'choices' => [
                    'Точний збіг (exact)' => 'exact',
                    'Містить (contains)' => 'contains',
                ],
                'help' => 'exact — адреса From повністю співпадає; contains — адреса From містить указаний фрагмент',
            ])
            ->add('filteractive', ChoiceType::class,[
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
            'data_class' => EmailSenderFilter::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'emailsenderfilter_form',
        ]);
    }
}
