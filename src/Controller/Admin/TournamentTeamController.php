<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use App\Entity\Tournament;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournament/{id}/teams')]
#[IsGranted('ROLE_ADMIN')]
class TournamentTeamController extends AbstractController
{
    #[Route('/', name: 'app_admin_tournament_teams', methods: ['GET', 'POST'])]
    public function index(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager,
        TeamRepository $teamRepository
    ): Response {
        $team = new Team();
        $team->setTournament($tournament);

        // Formulario rápido para añadir pareja
        $form = $this->createFormBuilder($team)
            ->add('player1', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Jugador 1',
                'attr' => ['class' => 'bg-gray-50 border-0 rounded-xl w-full p-3']
            ])
            ->add('player2', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Jugador 2',
                'attr' => ['class' => 'bg-gray-50 border-0 rounded-xl w-full p-3']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Pareja añadida al torneo.');
            return $this->redirectToRoute('app_admin_tournament_teams', ['id' => $tournament->getId()]);
        }

        return $this->render('admin/tournament/teams.html.twig', [
            'tournament' => $tournament,
            'teams' => $teamRepository->findBy(['tournament' => $tournament]),
            'form' => $form->createView(),
        ]);
    }
}
