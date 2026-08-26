<?php

namespace App\Form;

use App\Entity\Component;
use App\Entity\Ingredient;
use App\Entity\Unit;
use App\Form\Type\CustomAddselectType;
use App\Repository\IngredientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComponentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('drag', ButtonType::class, [
                'label' => '☰',
                'attr' => [
                    'class' => 'drag-handle component'
                ]
            ])
            ->add(
                'position',
                HiddenType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control position'
                    ]
                ]
            )
            ->add('ingredient', CustomAddselectType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'placeholder' => 'Select ingredient',
                'required' => true,
                'query_builder' => static function (IngredientRepository $repository) {
                    return $repository->createQueryBuilder('i')
                        ->leftJoin('i.unit', 'u')->addSelect('u')
                        ->orderBy('i.name', 'ASC');
                },
                'choice_attr' => static function (?Ingredient $ingredient): array {
                    if ($ingredient?->getUnit() === null) {
                        return [
                            'data-unit-id' => '',
                            'data-unit-label' => '',
                        ];
                    }

                    $unit = $ingredient->getUnit();

                    return [
                        'data-unit-id' => (string) $unit->getId(),
                        'data-unit-label' => (string) ($unit->getShortName() ?? $unit->getName() ?? ''),
                    ];
                },
                'attr' => [
                    'data-entity' => 'Ingredient',
                    'search-field' => 'name',
                    'require-fields' => 'name,sku',
                    'class' => 'mb-1 form-control ingredient',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'attr' => [
                    'class' => 'mb-1 form-control quantity'
                ]
            ])
            ->add('unit', EntityType::class, [
                'class' => Unit::class,
                'choice_label' => 'name',
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'd-none component-unit-input',
                ],
            ])
            ->add(
                'textunit',
                TextType::class,
                [
                    'attr' => [
                        'class' => 'mb-1 form-control position'
                    ]
                ]
            );

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $component = $event->getData();
            if (!$component instanceof Component) {
                return;
            }

            $ingredientUnit = $component->getIngredient()?->getUnit();
            if ($ingredientUnit !== null) {
                $component->setUnit($ingredientUnit);
            }
        });
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
