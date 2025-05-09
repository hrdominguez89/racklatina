<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\ExternalUserData;
use App\Enum\UserRoleType;
use App\Repository\RoleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, TextType, IntegerType, ChoiceType, SubmitType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('nationalIdNumber', IntegerType::class, [
                'label' => 'DNI <span style="color:red">*</span>',
                'label_html' => true,
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contacto <span style="color:red">*</span>',
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
                'label' => 'Razón social <span style="color:red">*</span>',
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
                'label' => 'Perfil <span style="color:red">*</span>',
                'label_html' => true,
                'choices' => array_combine(
                    array_map(fn($r) => ucfirst(strtolower(substr($r->getName(), 5))), $roles),
                    array_map(fn($r) => $r->getId(), $roles)
                ),
                'placeholder' => 'Seleccione un perfil',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
