<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UsuarioClienteRacklatinaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => false
            ])
            ->add('lastName', TextType::class, [
                'required' => false
            ])
            ->add('empresa', TextType::class, [
                'required' => false
            ])
            ->add('emailCorporativo', TextType::class, [
                'required' => false
            ])
            ->add('celular', TextType::class, [
                'required' => false
            ])
            ->add('segmento', TextType::class, [
                'required' => false
            ])
            ->add('sector', TextType::class, [
                'required' => false
            ])
            ->add('cargo', TextType::class, [
                'required' => false
            ])
            ->add('pais', TextType::class, [
                'required' => false
            ])
            ->add('provincia', TextType::class, [
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
