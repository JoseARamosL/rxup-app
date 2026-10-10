<?php

namespace App\Controller\Admin;

use App\Entity\Availability;
use App\Entity\Tournament;
use App\Repository\AvailabilityRepository;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournament/{id}/availability')]
#[IsGranted('ROLE_ADMIN')]
class AvailabilityController extends AbstractController
{
    #[Route('', name: 'app_admin_availability_index', methods: ['GET'])]
    public function index(
        Tournament $tournament,
        TeamRepository $teamRepository,
        AvailabilityRepository $availabilityRepository
    ): Response {
        $teams = $teamRepository->findBy(['tournament' => $tournament]);

        // Agrupar disponibilidades por equipo y luego por fecha
        $availsByTeam = [];
        foreach ($teams as $team) {
            $avails = $availabilityRepository->findBy(
                ['team' => $team, 'tournament' => $tournament],
                ['date' => 'ASC', 'startTime' => 'ASC']
            );
            $grouped = [];
            foreach ($avails as $a) {
                $grouped[$a->getDate()->format('Y-m-d')][] = $a;
            }
            $availsByTeam[$team->getId()] = $grouped;
        }

        return $this->render('admin/availability/index.html.twig', [
            'tournament'    => $tournament,
            'teams'         => $teams,
            'availsByTeam'  => $availsByTeam,
        ]);
    }

    #[Route('/add', name: 'app_admin_availability_add', methods: ['POST'])]
    public function add(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $em,
        TeamRepository $teamRepository,
        AvailabilityRepository $availabilityRepository
    ): Response {
        $teamId    = $request->request->get('team_id');
        $dateStr   = $request->request->get('date');
        $timeStr   = $request->request->get('start_time');

        $team = $teamRepository->find($teamId);
        if (!$team || !$dateStr || !$timeStr) {
            $this->addFlash('error', 'Datos incompletos.');
            return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
        }

        $date      = \DateTime::createFromFormat('Y-m-d', $dateStr);
        $startTime = \DateTime::createFromFormat('H:i', $timeStr);

        // Evitar duplicados
        $existing = $availabilityRepository->findOneBy([
            'team'      => $team,
            'tournament'=> $tournament,
            'date'      => $date,
            'startTime' => $startTime,
        ]);

        if (!$existing) {
            $avail = new Availability();
            $avail->setTeam($team)
                  ->setTournament($tournament)
                  ->setDate($date)
                  ->setStartTime($startTime);
            $em->persist($avail);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
    }

    #[Route('/delete/{availId}', name: 'app_admin_availability_delete', methods: ['POST'])]
    public function delete(
        Tournament $tournament,
        int $availId,
        EntityManagerInterface $em,
        AvailabilityRepository $availabilityRepository
    ): Response {
        $avail = $availabilityRepository->find($availId);
        if ($avail) {
            $em->remove($avail);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
    }

    #[Route('/cross', name: 'app_admin_availability_cross', methods: ['GET'])]
    public function cross(
        Tournament $tournament,
        TeamRepository $teamRepository,
        AvailabilityRepository $availabilityRepository,
        GameRepository $gameRepository
    ): Response {
        $teams = $teamRepository->findBy(['tournament' => $tournament]);

        // Cargar todas las disponibilidades indexadas por team_id → [date+time, ...]
        $slots = []; // [teamId => ['2025-01-13 17:00', ...]]
        foreach ($teams as $team) {
            $avails = $availabilityRepository->findBy(['team' => $team, 'tournament' => $tournament]);
            foreach ($avails as $a) {
                $key = $a->getDate()->format('Y-m-d') . ' ' . $a->getStartTime()->format('H:i');
                $slots[$team->getId()][] = $key;
            }
        }

        // Partidos ya jugados o programados (para no repetir)
        $playedPairs = [];
        $teamsWithGame = [];
        $games = $gameRepository->findBy(['tournament' => $tournament]);
        foreach ($games as $game) {
            if ($game->getTeam1() && $game->getTeam2()) {
                $a = min($game->getTeam1()->getId(), $game->getTeam2()->getId());
                $b = max($game->getTeam1()->getId(), $game->getTeam2()->getId());
                $playedPairs["{$a}-{$b}"] = true;
                $teamsWithGame[$game->getTeam1()->getId()] = true;
                $teamsWithGame[$game->getTeam2()->getId()] = true;
            }
        }

        // Cruce: para cada par de equipos que no hayan jugado, buscar slots comunes
        $suggestions = [];
        $teamsArr = array_values($teams);
        for ($i = 0; $i < count($teamsArr); $i++) {
            for ($j = $i + 1; $j < count($teamsArr); $j++) {
                $t1 = $teamsArr[$i];
                $t2 = $teamsArr[$j];

                $a = min($t1->getId(), $t2->getId());
                $b = max($t1->getId(), $t2->getId());
                if (isset($playedPairs["{$a}-{$b}"])) continue;
                if (isset($teamsWithGame[$t1->getId()]) || isset($teamsWithGame[$t2->getId()])) continue;

                $s1 = $slots[$t1->getId()] ?? [];
                $s2 = $slots[$t2->getId()] ?? [];
                $common = array_intersect($s1, $s2);

                if (!empty($common)) {
                    $suggestions[] = [
                        'team1'  => $t1,
                        'team2'  => $t2,
                        'slots'  => array_values($common),
                    ];
                }
            }
        }

        // Ordenar sugerencias por fecha del primer slot disponible
        usort($suggestions, fn($a, $b) => $a['slots'][0] <=> $b['slots'][0]);

        // Detectar parejas con disponibilidad pero sin ningún cruce posible
        $teamsInSuggestions = [];
        foreach ($suggestions as $s) {
            $teamsInSuggestions[$s['team1']->getId()] = true;
            $teamsInSuggestions[$s['team2']->getId()] = true;
        }

        $strandedTeams = [];
        foreach ($teams as $team) {
            $hasSlots = !empty($slots[$team->getId()]);
            $inSuggestion = isset($teamsInSuggestions[$team->getId()]);
            $alreadyScheduled = isset($teamsWithGame[$team->getId()]);
            if ($hasSlots && !$inSuggestion && !$alreadyScheduled) {
                $strandedTeams[] = $team;
            }
        }

        return $this->render('admin/availability/cross.html.twig', [
            'tournament'   => $tournament,
            'suggestions'  => $suggestions,
            'strandedTeams'=> $strandedTeams,
        ]);
    }

    #[Route('/create-game', name: 'app_admin_availability_create_game', methods: ['POST'])]
    public function createGame(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $em,
        TeamRepository $teamRepository
    ): Response {
        $team1Id  = $request->request->get('team1_id');
        $team2Id  = $request->request->get('team2_id');
        $slot     = $request->request->get('slot');     // "2025-01-13 17:00"
        $phase    = trim($request->request->get('phase', ''));
        $court    = trim($request->request->get('court', ''));

        $team1 = $teamRepository->find($team1Id);
        $team2 = $teamRepository->find($team2Id);

        if (!$team1 || !$team2 || !$slot) {
            $this->addFlash('error', 'Datos incompletos.');
            return $this->redirectToRoute('app_admin_availability_cross', ['id' => $tournament->getId()]);
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i', $slot);

        $game = new \App\Entity\Game();
        $game->setTournament($tournament)
             ->setTeam1($team1)
             ->setTeam2($team2)
             ->setDate($date)
             ->setPhase($phase ?: 'Jornada')
             ->setCourt($court ?: null);

        $em->persist($game);
        $em->flush();

        $this->addFlash('success', 'Partido creado correctamente.');
        return $this->redirectToRoute('app_admin_availability_cross', ['id' => $tournament->getId()]);
    }
}
