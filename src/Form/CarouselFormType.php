<?php

namespace App\Form;

use App\Entity\Carousel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CarouselFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['data']->getId() !== null;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre descriptivo',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: Banner principal, Promoción verano, etc.'
                ],
                'required' => true
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Imagen',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'help' => 'Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 4MB',
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPG, PNG, GIF, WEBP)',
                        'maxSizeMessage' => 'La imagen no puede superar los 4MB'
                    ])
                ]
            ])
            ->add('href', UrlType::class, [
                'label' => 'Enlace (URL)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://ejemplo.com/pagina'
                ],
                'help' => 'URL a la que redirigirá al hacer clic en la imagen (opcional)'
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Mostrar desde',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Fecha y hora desde la cual el carrusel estará visible (opcional)'
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Mostrar hasta',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Fecha y hora hasta la cual el carrusel estará visible (opcional)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Carousel::class,
        ]);
    }
}
