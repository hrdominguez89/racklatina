<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRoleType;
use App\Repository\ClientesRepository;
use App\Repository\RoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, TextType, ChoiceType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationFormType extends AbstractType
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository, private ClientesRepository $clienteRepository)
    {
        $this->clienteRepository = $clienteRepository;
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
                    new Callback(function ($cuit, ExecutionContextInterface $context) {
                        $cliente = $this->clienteRepository->findOneBy(['cuit' => $cuit]);
                        if (!$cliente) {
                            $context
                                ->buildViolation('El CUIT no existe en la base de datos de clientes.')
                                ->addViolation();
                        }
                    }),
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
                'label' => 'Contraseña',
                'label' => 'Contraseña <span style="color:red">*</span>',
                'label_html' => true,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('companyName', TextType::class, [
                'mapped' => false,
                'label' => 'Empresa',
                'label_html' => true
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
                    $name = ucfirst(strtolower(substr($r->getName(), 5)));
                    // ✅ Corrección de "Administracion" → "Administración"
                    return $name === 'Administracion' ? 'Administración' : $name;
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
                        'COMPRADOR' => 'Accede a órdenes de compra, fechas de entrega y descarga de facturas, etc.',
                        'ADMINISTRACION' => 'Accede al estado y gestión de facturas y a la información administrativa vinculada a la cuenta.',
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
