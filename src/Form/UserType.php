<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nombre completo',
                'attr' => ['placeholder' => 'Ej: Juan Pérez']
            ])
            ->add('email', null, [
                'label' => 'Correo electrónico',
                'attr' => ['placeholder' => 'ejemplo@correo.com']
            ])
            ->add('phone', null, [
                'label' => 'Teléfono de contacto',
                'attr' => ['placeholder' => '600 000 000']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
