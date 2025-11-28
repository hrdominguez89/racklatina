<?php

namespace App\Form;

use App\Entity\Pais;
use App\Entity\Provincias;
use App\Entity\Servicios;
use App\Entity\ServiciosMarcas;
use App\Entity\ServiciosTipo;
use App\Repository\ProvinciasRepository;
use App\Repository\PaisRepository;
use App\Repository\ServiciosMarcasRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServicesFormType extends AbstractType
{
    private ProvinciasRepository $provinciasRepository;
    private PaisRepository $paisRepository;
    private ServiciosMarcasRepository $marcasRepository;

    public function __construct(
        ProvinciasRepository $provinciasRepository,
        PaisRepository $paisRepository,
        ServiciosMarcasRepository $marcasRepository
    ) {
        $this->provinciasRepository = $provinciasRepository;
        $this->paisRepository = $paisRepository;
        $this->marcasRepository = $marcasRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Servicios|null $service */
        $service = $options['data'] ?? null;
        $paisId = $service?->getServicepaisid();

        // Verificar si los campos de contacto deben estar deshabilitados (para usuarios externos)
        $disableContactFields = $options['disable_contact_fields'] ?? false;

        // Preparar choices para país
        $paises = $this->paisRepository->findAll();
        $paisesChoices = [];
        foreach ($paises as $pais) {
            $paisesChoices[$pais->getPaisNombre()] = $pais->getPaisId();
        }

        // Preparar choices para marcas
        $marcas = $this->marcasRepository->findAll();
        $marcasChoices = [];
        foreach ($marcas as $marca) {
            $marcasChoices[$marca->getServiceproddescrip()] = $marca->getServiceprodid();
        }

        // Preparar choices para provincias (todas, para JS)
        $provincias = $this->provinciasRepository->findAll();
        $provinciasChoices = [];
        foreach ($provincias as $provincia) {
            $provinciasChoices[$provincia->getProvinciaNombre()] = $provincia->getProvinciaId();
        }

        $builder
            // ->add('servicedate', DateTimeType::class, [
            //     'label' => 'Fecha de Servicio',
            //     'widget' => 'single_text',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('serviceempresa', TextType::class, [
                'label' => 'Empresa',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de la empresa']
            ])
            // ->add('servicecuit', TextType::class, [
            //     'label' => 'CUIT',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese el CUIT']
            // ])
            ->add('servicecontacto', TextType::class, [
                'label' => 'Contacto',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nombre del contacto',
                    'readonly' => $disableContactFields
                ]
            ])
            ->add('serviceemail', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'correo@ejemplo.com',
                    'readonly' => $disableContactFields
                ]
            ])
            ->add('servicedireccion', TextType::class, [
                'label' => 'Dirección',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dirección completa']
            ])
            // ->add('servicecodpostal', TextType::class, [
            //     'label' => 'Código Postal',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Código postal']
            // ])
            // ->add('servicetelefono', TextType::class, [
            //     'label' => 'Teléfono',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Teléfono de contacto']
            // ])
            ->add('servicepaisid', ChoiceType::class, [
                'label' => 'País',
                'placeholder' => 'Seleccione un país',
                'required' => true,
                'choices' => $paisesChoices,
                'attr' => ['class' => 'form-select', 'id' => 'pais-select']
            ])
            ->add('serviceprovinciaid', ChoiceType::class, [
                'label' => 'Provincia',
                'placeholder' => 'Seleccione primero un país',
                'required' => true,
                'choices' => $provinciasChoices,
                'attr' => ['class' => 'form-select', 'id' => 'provincia-select']
            ])
            ->add('servicelocalidad', TextType::class, [
                'label' => 'Localidad',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese la localidad']
            ])
            ->add('servicetransporte', ChoiceType::class, [
                'label' => 'Transporte',
                'choices' => [
                    'Aplica' => 'Aplica',
                    'No Aplica' => 'No Aplica'
                ],
                'required' => false,
                'placeholder' => 'Seleccione una opción',
                'attr' => ['class' => 'form-select']
            ])
            // ->add('servicetransportenombre', TextType::class, [
            //     'label' => 'Nombre del Transporte',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre del transporte']
            // ])
            // ->add('servicetransportedireccion', TextType::class, [
            //     'label' => 'Dirección del Transporte',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Dirección del transporte']
            // ])
            ->add('servicemarcaid', ChoiceType::class, [
                'label' => 'Marca',
                'placeholder' => 'Seleccione una marca',
                'required' => true,
                'choices' => $marcasChoices,
                'attr' => ['class' => 'form-select']
            ])
            ->add('servicecodcatalogo', TextType::class, [
                'label' => 'Código Catálogo',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese el código de catálogo']
            ])
            // ->add('serviceserie', TextType::class, [
            //     'label' => 'Serie',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese la serie']
            // ])
            ->add('servicenroserie', TextType::class, [
                'label' => 'Número de Serie',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese el número de serie']
            ])
            ->add('servicefalla', TextareaType::class, [
                'label' => 'Descripción de la Falla',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describa la falla'
                ]
            ])
            // ->add('servicemeses', TextType::class, [
            //     'label' => 'Meses',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Ingrese los meses']
            // ])
            // ->add('serviceTypeID', EntityType::class, [
            //     'class' => ServiciosTipo::class,
            //     'choice_label' => 'serviceTypeDescrip',
            //     'choice_value' => 'serviceTypeID',
            //     'label' => 'Tipo de Servicio',
            //     'placeholder' => 'Seleccione un tipo',
            //     'required' => false,
            //     'attr' => ['class' => 'form-select']
            // ])
            // ->add('servicenroseguimiento', TextType::class, [
            //     'label' => 'Nro. Seguimiento',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Número de seguimiento']
            // ])
            // ->add('servicenroticket', TextType::class, [
            //     'label' => 'Nro. Ticket',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Número de ticket']
            // ])
            // ->add('servicenrorma', TextType::class, [
            //     'label' => 'Nro. RMA',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'Número de RMA']
            // ])
            // ->add('servicesucursalid', IntegerType::class, [
            //     'label' => 'Sucursal ID',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'ID de sucursal']
            // ])
            // ->add('serviceanalistaid', IntegerType::class, [
            //     'label' => 'Analista ID',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'ID de analista']
            // ])
            // ->add('servicevendedorid', IntegerType::class, [
            //     'label' => 'Vendedor ID',
            //     'required' => false,
            //     'attr' => ['class' => 'form-control', 'placeholder' => 'ID de vendedor']
            // ])
            // ->add('serviceobservaciones', TextareaType::class, [
            //     'label' => 'Observaciones',
            //     'required' => false,
            //     'attr' => [
            //         'class' => 'form-control',
            //         'rows' => 4,
            //         'placeholder' => 'Observaciones adicionales'
            //     ]
            // ])
            // ->add('servicestatus', ChoiceType::class, [
            //     'label' => 'Estado',
            //     'choices' => [
            //         'Pendiente' => 1,
            //         'En Proceso' => 2,
            //         'Completado' => 3,
            //         'Rechazado' => 4
            //     ],
            //     'required' => false,
            //     'placeholder' => 'Seleccione un estado',
            //     'attr' => ['class' => 'form-select']
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Servicios::class,
            'disable_contact_fields' => false,
        ]);
    }
}
