<?php

namespace App\Form;

use App\Entity\Pais;
use App\Entity\Provincias;
use App\Entity\ServiceRequests;
use App\Entity\ServiciosMarcas;
use App\Repository\ProvinciasRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ServiceRequestsFormType extends AbstractType
{
    private ProvinciasRepository $provinciasRepository;

    public function __construct(ProvinciasRepository $provinciasRepository)
    {
        $this->provinciasRepository = $provinciasRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ServiceRequests|null $serviceRequest */
        $serviceRequest = $options['data'] ?? null;
        $paisId = $serviceRequest?->getPais()?->getPaisId();

        $builder
            ->add('pais', EntityType::class, [
                'class' => Pais::class,
                'choice_label' => 'paisNombre',
                'choice_value' => 'paisId',
                'label' => 'País',
                'placeholder' => 'Seleccione un país',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('provincia', EntityType::class, [
                'class' => Provincias::class,
                'choice_label' => 'provinciaNombre',
                'label' => 'Provincia',
                'placeholder' => 'Seleccione primero un país',
                'required' => true,
                'query_builder' => function (ProvinciasRepository $repo) use ($paisId) {
                    $qb = $repo->createQueryBuilder('p');
                    if ($paisId) {
                        $qb->where('p.paisId = :paisId')
                           ->setParameter('paisId', $paisId);
                    } else {
                        // Si no hay país, no mostrar ninguna provincia inicialmente
                        // Pero permitir que se envíe cualquier provincia válida
                        $qb->where('1=1'); // Devuelve todas para validación
                    }
                    return $qb->orderBy('p.provinciaNombre', 'ASC');
                },
                'attr' => ['class' => 'form-select']
            ])
            ->add('localidad', TextType::class, [
                'label' => 'Localidad',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese la localidad']
            ])
            ->add('empresa', TextType::class, [
                'label' => 'Empresa',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de la empresa']
            ])
            ->add('contacto', TextType::class, [
                'label' => 'Nombre y Apellido',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre completo del contacto']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'correo@ejemplo.com']
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección de entrega',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dirección completa']
            ])
            ->add('transporteNombre', ChoiceType::class, [
                'label' => 'Nombre Transporte',
                'choices' => [
                    'Aplica' => 'Aplica',
                    'No Aplica' => 'No Aplica'
                ],
                'required' => true,
                'placeholder' => 'Seleccione una opción',
                'attr' => ['class' => 'form-select']
            ])
            ->add('marca', EntityType::class, [
                'class' => ServiciosMarcas::class,
                'choice_label' => 'serviceproddescrip',
                'choice_value' => 'serviceprodid',
                'label' => 'Marca',
                'placeholder' => 'Seleccione una marca',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('codCatalogo', TextType::class, [
                'label' => 'Código Catálogo',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Código del producto']
            ])
            ->add('nroSerie', TextType::class, [
                'label' => 'Número de Serie',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Número de serie del equipo']
            ])
            ->add('falla', TextareaType::class, [
                'label' => 'Descripción de la Falla',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describa detalladamente la falla del equipo'
                ]
            ])
            ->add('adquiridoUltimos12Meses', ChoiceType::class, [
                'label' => '¿Ha adquirido este equipo en los últimos 12 meses?',
                'choices' => [
                    'Sí' => true,
                    'No' => false
                ],
                'required' => true,
                'placeholder' => 'Seleccione una opción',
                'attr' => ['class' => 'form-select']
            ])
            ->add('facturaCompra', FileType::class, [
                'label' => 'Factura de compra (PDF)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Por favor suba un archivo PDF válido',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceRequests::class,
        ]);
    }
}
