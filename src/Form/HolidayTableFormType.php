<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class HolidayTableFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('holiday_table_id', HiddenType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('event_name', TextType::class, [
                'label' => 'holiday_table.event_name',
                'required' => false,
                'attr' => [
                    'class' => 'form-control page-form__control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new NotBlank(groups: ['save']),
                ],
            ])
            ->add('event_date', DateType::class, [
                'label' => 'holiday_table.event_date',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control page-form__control',
                ],
                'constraints' => [
                    new NotNull(groups: ['save']),
                ],
            ])
            ->add('men_count', IntegerType::class, [
                'label' => 'holiday_table.men_count',
                'attr' => [
                    'class' => 'form-control page-form__control',
                    'min' => 0,
                ],
                'constraints' => [
                    new NotNull(),
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add('women_count', IntegerType::class, [
                'label' => 'holiday_table.women_count',
                'attr' => [
                    'class' => 'form-control page-form__control',
                    'min' => 0,
                ],
                'constraints' => [
                    new NotNull(),
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add('recipes', ChoiceType::class, [
                'label' => 'holiday_table.recipes',
                'choices' => $options['recipe_choices'],
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'd-none',
                    'data-holiday-table-target' => 'recipesSelect',
                ],
                'constraints' => [
                    new Count(min: 1, minMessage: 'holiday_table.recipes_required'),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function ($event): void {
            $data = $event->getData();
            $menCount = (int) ($data['men_count'] ?? 0);
            $womenCount = (int) ($data['women_count'] ?? 0);

            if ($menCount + $womenCount < 1) {
                $event->getForm()->addError(new FormError('holiday_table.guests_required'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'recipe_choices' => [],
            'translation_domain' => 'messages',
            'validation_groups' => ['Default'],
        ]);
        $resolver->setAllowedTypes('recipe_choices', 'array');
    }
}
