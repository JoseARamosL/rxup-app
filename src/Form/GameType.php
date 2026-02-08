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
            ->add('scoreTeamA', TextType::class, [
                'label' => 'Resultado Pareja Local (A)',
                'required' => false,
                'attr' => ['placeholder' => 'Ej: 6-4 / 7-5']
            ])
            ->add('scoreTeamB', TextType::class, [
                'label' => 'Resultado Pareja Visitante (B)',
                'required' => false,
                'attr' => ['placeholder' => 'Ej: 4-6 / 5-7']
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
