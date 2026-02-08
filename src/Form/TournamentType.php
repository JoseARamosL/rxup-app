<?php

namespace App\Form;

use App\Entity\Tournament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Torneo o Liga',
                'attr' => ['placeholder' => 'Ej: I Torneo de Verano RXUP']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Tipo de Competición',
                'choices' => [
                    'Torneo (Eliminatorias)' => 'TOURNAMENT',
                    'Liga (Puntos)' => 'LEAGUE',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Normativa y detalles',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('hasRanking', CheckboxType::class, [
                'label' => '¿Activar Clasificación Automática?',
                'required' => false,
                'data' => true, // Marcado por defecto
                'attr' => ['class' => 'rounded text-indigo-600 focus:ring-indigo-500']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}
