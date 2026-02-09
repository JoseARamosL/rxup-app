<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Importante añadir esto
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tournament = $options['data']->getTournament();

        $builder
            ->add('team1', EntityType::class, [
                'class' => Team::class,
                'label' => 'Pareja Local',
                'query_builder' => function (EntityRepository $er) use ($tournament) {
                    return $er->createQueryBuilder('t')
                        ->where('t.tournament = :tournament')
                        ->setParameter('tournament', $tournament);
                },
                'choice_label' => function (Team $team) {
                    return $team->getPlayer1()->getName() . ' / ' . $team->getPlayer2()->getName();
                },
                'attr' => ['class' => 'bg-gray-50 border-0 rounded-xl w-full p-4']
            ])
            ->add('team2', EntityType::class, [
                'class' => Team::class,
                'label' => 'Pareja Visitante',
                'query_builder' => function (EntityRepository $er) use ($tournament) {
                    return $er->createQueryBuilder('t')
                        ->where('t.tournament = :tournament')
                        ->setParameter('tournament', $tournament);
                },
                'choice_label' => function (Team $team) {
                    return $team->getPlayer1()->getName() . ' / ' . $team->getPlayer2()->getName();
                },
                'attr' => ['class' => 'bg-gray-50 border-0 rounded-xl w-full p-4']
            ])
            ->add('phase', null, [
                'label' => 'Fase',
                'attr' => ['placeholder' => 'Ej: Fase de Grupos']
            ])
            ->add('court', null, [
                'label' => 'Pista'
            ])
            ->add('date', null, [
                'label' => 'Fecha y Hora',
                'widget' => 'single_text', // Esto hace que sea más fácil de manejar en HTML5
            ])
            // AÑADIMOS EL CAMPO SCORE AQUÍ
            ->add('score', TextType::class, [
                'required' => false,
                'label' => 'Resultado',
                'attr' => [
                    'placeholder' => 'Ej: 6-4 / 6-2'
                ]
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
