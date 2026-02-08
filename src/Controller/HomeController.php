<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Si el usuario ya está logueado, lo mandamos directo a los torneos
        if ($this->getUser()) {
            return $this->redirectToRoute('app_tournament_index');
        }

        return $this->render('home/index.html.twig');
    }
}
