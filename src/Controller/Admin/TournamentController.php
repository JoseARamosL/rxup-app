<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/tournament')]
class TournamentController extends AbstractController
{
    // Listado de torneos
    #[Route('/', name: 'app_admin_tournament_index', methods: ['GET'])]
    public function index(TournamentRepository $tournamentRepository): Response
    {
        return $this->render('admin/tournament/index.html.twig', [
            'tournaments' => $tournamentRepository->findAll(),
        ]);
    }

    // Formulario de creación
    #[Route('/new', name: 'app_admin_tournament_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tournament = new Tournament();
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournament);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_tournament_index');
        }

        return $this->render('admin/tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        // Reutilizamos el mismo formulario que usamos para crear
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Al ser un objeto que ya viene de la base de datos (por el id),
            // solo necesitamos hacer flush para guardar los cambios.
            $entityManager->flush();

            $this->addFlash('success', 'Torneo actualizado correctamente.');

            return $this->redirectToRoute('app_admin_tournament_index');
        }

        return $this->render('admin/tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournament->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();

            $this->addFlash('success', 'Torneo eliminado definitivamente.');
        }

        return $this->redirectToRoute('app_admin_tournament_index');
    }
}
