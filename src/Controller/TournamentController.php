<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Repository\GameRepository;
use App\Repository\TournamentRepository;
use App\Service\RankingCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TournamentController extends AbstractController
{
    #[Route('/torneos', name: 'app_tournament_index')]
    public function index(TournamentRepository $repository): Response
    {
        // Aquí el usuario verá todos los torneos disponibles
        return $this->render('tournament/index.html.twig', [
            'tournaments' => $repository->findAll(),
        ]);
    }

    #[Route('/torneos/{id}', name: 'app_tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament, RankingCalculator $calculator): Response
    {
        // Agrupamos los partidos por fase para que el usuario los vea ordenados
        $groupedGames = [];
        foreach ($tournament->getGames() as $game) {
            $groupedGames[$game->getPhase()][] = $game;
        }

        // Calculamos el ranking solo si el torneo lo tiene activo
        $stats = $tournament->hasRanking() ? $calculator->calculate($tournament) : [];

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'groupedGames' => $groupedGames,
            'stats' => $stats,
        ]);
    }

    #[Route('/mis-partidos', name: 'app_my_games')]
    public function myGames(GameRepository $gameRepository): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // Buscamos partidos donde el usuario sea player1 o player2 de team1 O de team2
        $allGames = $gameRepository->createQueryBuilder('g')
            ->leftJoin('g.team1', 't1')
            ->leftJoin('g.team2', 't2')
            ->where('t1.player1 = :user OR t1.player2 = :user OR t2.player1 = :user OR t2.player2 = :user')
            ->setParameter('user', $user)
            ->orderBy('g.date', 'ASC')
            ->getQuery()
            ->getResult();

        $upcomingGames = [];
        $pastGames = [];

        foreach ($allGames as $game) {
            if (empty($game->getScore())) {
                $upcomingGames[] = $game;
            } else {
                // Más recientes primero para los finalizados
                array_unshift($pastGames, $game);
            }
        }

        return $this->render('tournament/my_games.html.twig', [
            'upcomingGames' => $upcomingGames,
            'pastGames' => $pastGames,
        ]);
    }
}
