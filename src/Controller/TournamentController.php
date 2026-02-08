<?php

namespace App\Controller;

use App\Repository\TournamentRepository;
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
}
