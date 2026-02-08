<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $playerOptions = [
            'class' => User::class,
            'choice_label' => 'name', // Usará el campo name que acabamos de crear
            'placeholder' => 'Seleccionar jugador...',
            'required' => false,
        ];

        $builder
            ->add('player1', EntityType::class, $playerOptions)
            ->add('player2', EntityType::class, $playerOptions)
            ->add('player3', EntityType::class, $playerOptions)
            ->add('player4', EntityType::class, $playerOptions)
            ->add('phase', TextType::class, [
                'label' => 'Fase (Jornada 1, Semifinal...)',
                'attr' => ['placeholder' => 'Ej: Jornada 1']
            ])
            ->add('court', TextType::class, ['label' => 'Pista', 'required' => false])
            ->add('date', DateTimeType::class, [
                'label' => 'Fecha y Hora',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('score', TextType::class, [
                'label' => 'Resultado del Partido',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ej: 6-2, 2-6, 6-4',
                    'class' => 'text-center font-mono'
                ],
                'help' => 'Usa guiones para los sets y comas para separarlos.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
