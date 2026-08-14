<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRoleType;
use App\Repository\RoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, TextType, ChoiceType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roles = $this->roleRepository->findBy(['type' => UserRoleType::EXTERNAL]);

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nombre <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Apellido <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('cuit', TextType::class, [
                'mapped' => false,
                'label' => 'CUIT/RUT <span style="color:red">*</span>',
                'attr' => ['placeholder' => 'Ejemplo: 30-67969632-3'],
                'label_html' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'El CUIT/RUT es obligatorio.']),
                ],
            ])
            // ->add('nationalIdNumber', IntegerType::class, [
            //     'label' => 'nationalIdNumber <span style="color:red">*</span>',
            //     'label_html' => true,
            //     'required' => true,
            //     'mapped' => false,

            // ])
            ->add('email', EmailType::class, [
                'label' => 'Email <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Contraseña <span style="color:red">*</span>',
                'label_html' => true,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('companyName', TextType::class, [
                'mapped' => false,
                'label' => 'Razón Social <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('domicilio', TextType::class, [
                'mapped' => false,
                'label' => 'Domicilio <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('codigoPostal', TextType::class, [
                'mapped' => false,
                'label' => 'Código Postal <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('localidad', TextType::class, [
                'mapped' => false,
                'label' => 'Localidad <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('provincia', TextType::class, [
                'mapped' => false,
                'label' => 'Provincia <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
            ])
            ->add('tipoCliente', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Tipo de Cliente <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
                'placeholder' => 'Seleccioná una opción',
                'choices' => [
                    'Ingenierías'               => 'Ingenierías',
                    'Usuario Final'             => 'Usuario Final',
                    'Distribuidor'              => 'Distribuidor',
                    'Integrador'                => 'Integrador',
                    'Tableristas'               => 'Tableristas',
                    'Fabricante de Maquinarias' => 'Fabricante de Maquinarias',
                    'Otros'                     => 'Otros',
                ],
            ])
            ->add('segmento', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Segmento <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
                'placeholder' => 'Seleccioná una opción',
                'choices' => [
                    'Logística y Grandes Comercios'             => 'Logística y Grandes Comercios',
                    'Cuidado Personal y Químico'                => 'Cuidado Personal y Químico',
                    'Pesqueras y afines'                        => 'Pesqueras y afines',
                    'Siderúrgico'                               => 'Siderúrgico',
                    'Energía'                                   => 'Energía',
                    'Oil & Gas'                                 => 'Oil & Gas',
                    'Fabricantes de Máquinas'                   => 'Fabricantes de Máquinas',
                    'Construcción'                              => 'Construcción',
                    'Manufactura'                               => 'Manufactura',
                    'Minería'                                   => 'Minería',
                    'Entes Públicos'                            => 'Entes Públicos',
                    'Telecomunicaciones'                        => 'Telecomunicaciones',
                    'Agroindustria'                             => 'Agroindustria',
                    'Alimentos y Bebidas'                       => 'Alimentos y Bebidas',
                    'Farmacéutico'                              => 'Farmacéutico',
                    'Laboratorios'                              => 'Laboratorios',
                    'Petroquímico'                              => 'Petroquímico',
                    'Pulpa, Papel y Madera'                     => 'Pulpa, Papel y Madera',
                    'Textil'                                    => 'Textil',
                    'Automotrices y Autopartistas'              => 'Automotrices y Autopartistas',
                    'Estatal'                                   => 'Estatal',
                    'Tableristas'                               => 'Tableristas',
                    'Metalúrgico'                               => 'Metalúrgico',
                    'IT'                                        => 'IT',
                    'Plástico'                                  => 'Plástico',
                    'Constructora'                              => 'Constructora',
                    'Aguas / Tratamiento de Aguas (WWW)'        => 'Aguas / Tratamiento de Aguas (WWW)',
                    'Food & Beverage'                           => 'Food & Beverage',
                    'Tableristas e Integradores/Ingeniería'     => 'Tableristas e Integradores/Ingeniería',
                    'Integradores'                              => 'Integradores',
                    'Distribuidores'                            => 'Distribuidores',
                    'OEMs'                                      => 'OEMs',
                    'Otros'                                     => 'Otros',
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'mapped' => false,
                'label' => 'Teléfono <span style="color:red">*</span>',
                'label_html' => true
            ])
            ->add('jobTitle', TextType::class, [
                'mapped' => false,
                'label' => 'Cargo <span style="color:red">*</span>',
                'label_html' => true
            ])
            ->add('role', ChoiceType::class, [
            'mapped' => false,
            'label' => 'Perfiles <span style="color:red">*</span>',
            'label_html' => true,
            'choices' => array_combine(
                array_map(function($r) {
                    $labels = [
                        'ROLE_COMPRADOR'      => 'Comprador',
                        'ROLE_ADMINISTRACION' => 'Administración',
                        'ROLE_INGENIERO_N1'   => 'Ingeniero – Nivel 1',
                        'ROLE_INGENIERO_N2'   => 'Ingeniero – Nivel 2',
                    ];
                    return $labels[$r->getName()] ?? ucfirst(strtolower(substr($r->getName(), 5)));
                }, $roles),
                array_map(fn($r) => $r->getId(), $roles)
            ),
                'choice_attr' => function($choice) use ($roles) {
                    // Obtener el rol correspondiente
                    $role = array_values(array_filter($roles, fn($r) => $r->getId() === $choice))[0] ?? null;
                    if (!$role) return [];

                    $roleName = strtoupper(substr($role->getName(), 5));

                    // Definir descripciones para cada rol
                    $descriptions = [
                        'COMPRADOR'      => 'Accede a órdenes de compra, fechas de entrega y descarga de facturas.',
                        'ADMINISTRACION' => 'Accede al estado y gestión de facturas e información administrativa de la cuenta.',
                        'INGENIERO_N1'   => 'Acceso a catálogo, fichas técnicas y stock.',
                        'INGENIERO_N2'   => 'Acceso a catálogo, fichas técnicas, stock y precios.',
                    ];

                    return [
                        'data-description' => $descriptions[$roleName] ?? ''
                    ];
                },
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'csrf_protection' => false]);
    }
}