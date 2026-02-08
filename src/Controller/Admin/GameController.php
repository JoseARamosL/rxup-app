<?php

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\Tournament;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\GameType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/admin/tournament/{id}/games')]
class GameController extends AbstractController
{
    #[Route('/', name: 'app_admin_game_index', methods: ['GET'])]
    public function index(Tournament $tournament, GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findBy(['tournament' => $tournament], ['date' => 'ASC']);

        // Agrupamos manualmente por fase
        $groupedGames = [];
        foreach ($games as $game) {
            $groupedGames[$game->getPhase()][] = $game;
        }

        return $this->render('admin/game/index.html.twig', [
            'tournament' => $tournament,
            'groupedGames' => $groupedGames, // Pasamos la variable ya agrupada
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
        EntityManagerInterface $entityManager
    ): Response {
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
}
