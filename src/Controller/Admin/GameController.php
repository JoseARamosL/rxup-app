<?php

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\GameType;
use App\Repository\TeamRepository;
use App\Service\RankingCalculator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

#[Route('/admin/tournament/{id}/games')]
class GameController extends AbstractController
{
    #[Route('/', name: 'app_admin_game_index', methods: ['GET', 'POST'])]
    public function index(
    Tournament $tournament,
    GameRepository $gameRepository,
    TeamRepository $teamRepository,
    Request $request,
    EntityManagerInterface $entityManager
    ): Response {
        // --- Lógica para CREAR PAREJA ---
        $team = new Team();
        $team->setTournament($tournament);

        $teamForm = $this->createFormBuilder($team)
            ->add('player1', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Selecciona Jugador 1',
            ])
            ->add('player2', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Selecciona Jugador 2',
            ])
            ->getForm();

        $teamForm->handleRequest($request);

        if ($teamForm->isSubmitted() && $teamForm->isValid()) {
            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', 'Pareja registrada con éxito.');
            return $this->redirectToRoute('app_admin_game_index', ['id' => $tournament->getId()]);
        }
        // --------------------------------

        $games = $gameRepository->findBy(['tournament' => $tournament], ['date' => 'ASC']);

        $groupedGames = [];
        foreach ($games as $game) {
            $groupedGames[$game->getPhase()][] = $game;
        }

        return $this->render('admin/game/index.html.twig', [
            'tournament' => $tournament,
            'groupedGames' => $groupedGames,
            'teams' => $teamRepository->findBy(['tournament' => $tournament]), // Pasamos las parejas existentes
            'teamForm' => $teamForm->createView(), // Pasamos el formulario a la vista
        ]);
    }

    #[Route('/new', name: 'app_admin_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        $game = new Game();
        $game->setTournament($tournament); // Vinculamos automáticamente al torneo actual

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($game);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_game_index', ['id' => $tournament->getId()]);
        }

        return $this->render('admin/game/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{gameId}/edit', name: 'app_admin_game_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'gameId')] Game $game,
        EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Solo necesitamos flush para actualizar

            return $this->redirectToRoute('app_admin_game_index', ['id' => $game->getTournament()->getId()]);
        }

        return $this->render('admin/game/edit.html.twig', [
            'tournament' => $game->getTournament(),
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/ranking', name: 'app_admin_tournament_ranking', methods: ['GET'])]
    public function ranking(Tournament $tournament, RankingCalculator $calculator): Response
    {
        // Si el torneo no tiene clasificación, redirigimos al calendario con un mensaje
        if (!$tournament->hasRanking()) {
            $this->addFlash('warning', 'Este torneo no tiene activada la clasificación.');
            return $this->redirectToRoute('app_admin_game_index', ['id' => $tournament->getId()]);
        }

        $stats = $calculator->calculate($tournament);

        return $this->render('admin/tournament/ranking.html.twig', [
            'tournament' => $tournament,
            'stats' => $stats,
        ]);
    }

    #[Route('/{gameId}/delete', name: 'app_admin_game_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(id: 'gameId')] Game $game,
        EntityManagerInterface $entityManager): Response
    {
        $tournamentId = $game->getTournament()->getId();

        // Verificamos el token de seguridad para evitar borrados accidentales o malintencionados
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->request->get('_token'))) {
            $entityManager->remove($game);
            $entityManager->flush();
            $this->addFlash('success', 'Partido eliminado correctamente.');
        }

        return $this->redirectToRoute('app_admin_game_index', ['id' => $tournamentId]);
    }
}
