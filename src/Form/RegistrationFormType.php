<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRoleType;
use App\Repository\RoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, TextType, ChoiceType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    private RoleRepository $roleRepository;

    // Letras (con acentos/ñ), números, espacios, apóstrofes y guiones. Nada de < > ; { } ` etc.
    private const NOMBRE_PATTERN = '/^[\p{L}\p{N}\s\'\-]+$/u';

    // Igual que NOMBRE_PATTERN pero admite ".", "," y "&" (razón social, direcciones: "Av. Rivadavia 1234, Piso 2", "Smith & Sons S.A.")
    private const DIRECCION_PATTERN = '/^[\p{L}\p{N}\s\'\-.,&]*$/u';

    // Formatos de CUIT/RUT reales en la base de Calypso son inconsistentes (con/sin guiones,
    // distinta cantidad de dígitos) — se valida solo que sean dígitos y guiones, sin forzar
    // una posición exacta, para no bloquear el registro de clientes ya existentes.
    private const CUIT_PATTERN = '/^[0-9\-]+$/';

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roles = $this->roleRepository->findBy(['type' => UserRoleType::EXTERNAL]);

        // Perfiles Ingeniero N1/N2 (Sprint 4.1) comentados: aún no disponibles para autoregistro
        $roles = array_values(array_filter($roles, function ($r) {
            return !in_array($r->getName(), ['ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2'], true);
        }));

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nombre <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'El nombre es obligatorio.']),
                    new Length(['max' => 60]),
                    new Regex(['pattern' => self::NOMBRE_PATTERN, 'message' => 'El nombre contiene caracteres no permitidos.']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Apellido <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'El apellido es obligatorio.']),
                    new Length(['max' => 60]),
                    new Regex(['pattern' => self::NOMBRE_PATTERN, 'message' => 'El apellido contiene caracteres no permitidos.']),
                ],
            ])
            ->add('cuit', TextType::class, [
                'mapped' => false,
                'label' => 'CUIT/RUT <span style="color:red">*</span>',
                'attr' => ['placeholder' => 'Ejemplo: 30-67969632-3'],
                'label_html' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'El CUIT/RUT es obligatorio.']),
                    new Length(['max' => 20]),
                    new Regex(['pattern' => self::CUIT_PATTERN, 'message' => 'El CUIT/RUT solo puede contener números y guiones.']),
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
                'constraints' => [
                    new NotBlank(['message' => 'El email es obligatorio.']),
                    new Email(['message' => 'Ingresá un email válido.']),
                ],
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
                'constraints' => [
                    new Length(['max' => 255]),
                    new Regex(['pattern' => self::DIRECCION_PATTERN, 'message' => 'La razón social contiene caracteres no permitidos.']),
                ],
            ])
            ->add('domicilio', TextType::class, [
                'mapped' => false,
                'label' => 'Domicilio <span style="color:red">*</span>',
                'label_html' => true,
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                    new Regex(['pattern' => self::DIRECCION_PATTERN, 'message' => 'El domicilio contiene caracteres no permitidos.']),
                ],
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
                        // 'ROLE_INGENIERO_N1'   => 'Ingeniero – Nivel 1',
                        // 'ROLE_INGENIERO_N2'   => 'Ingeniero – Nivel 2',
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
                        // 'INGENIERO_N1'   => 'Acceso a catálogo, fichas técnicas y stock.',
                        // 'INGENIERO_N2'   => 'Acceso a catálogo, fichas técnicas, stock y precios.',
                    ];

                    return [
                        'data-description' => $descriptions[$roleName] ?? ''
                    ];
                },
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class, 'csrf_protection' => false]);
    }
}
