<?php

namespace App\Form\Type;

use App\Entity\SamProject;
use App\Entity\ServerData;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ServerDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_server', ChoiceType::class,['choices' => [
                'Live'=>1,
                'Test'=>2,
                'Stage'=>3,
            ]])
            ->add('hostname', TextType::class)
            ->add('port', TextType::class)
            ->add('username', TextType::class)
            ->add('password', TextType::class);


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var ServerData|null $server */
            $server = $event->getData();
            if( $server === null){
                return;
            }
            $form   = $event->getForm();
            $fileLink = $server->getDumpLink();
            $fileName = basename($fileLink);
            $help = ($fileLink && $fileName)
                ? sprintf("Current file: <a href='%s'>%s</a>", $fileLink, $fileName)
                : "No file uploaded yet";
            if ($server instanceof ServerData ) {
                $form->add('dump_link_upload',
                    FileType::class,
                    [
                        'mapped' => false,
                        'required' => false,
                        'attr' => ['accept' => '.zip'],
                        'help' => $help,
                        'constraints' => [
                            new File([
                                'maxSize' => '10M',
                                'mimeTypes' => [
                                    'application/zip',
                                    'application/x-zip-compressed',
                                    'multipart/x-zip',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid ZIP file',
                            ]),
                        ],
                    ]
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServerData::class,
        ]);
    }
}
