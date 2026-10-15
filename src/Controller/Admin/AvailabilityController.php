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
        $teamId      = $request->request->get('team_id');
        $dateStr     = $request->request->get('date');
        $startStr    = $request->request->get('start_time');
        $endStr      = $request->request->get('end_time');

        $team = $teamRepository->find($teamId);
        if (!$team || !$dateStr || !$startStr || !$endStr) {
            $this->addFlash('error', 'Datos incompletos.');
            return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
        }

        $date      = \DateTime::createFromFormat('Y-m-d', $dateStr);
        $startTime = \DateTime::createFromFormat('H:i', $startStr);
        $endTime   = \DateTime::createFromFormat('H:i', $endStr);

        if (!$date || !$startTime || !$endTime) {
            $this->addFlash('error', 'Formato de fecha u hora incorrecto.');
            return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
        }

        if ($endTime <= $startTime) {
            $this->addFlash('error', 'La hora de fin debe ser posterior a la de inicio.');
            return $this->redirectToRoute('app_admin_availability_index', ['id' => $tournament->getId()]);
        }

        // Evitar duplicados exactos
        $existing = $availabilityRepository->findOneBy([
            'team'       => $team,
            'tournament' => $tournament,
            'date'       => $date,
            'startTime'  => $startTime,
        ]);

        if (!$existing) {
            $avail = new Availability();
            $avail->setTeam($team)
                  ->setTournament($tournament)
                  ->setDate($date)
                  ->setStartTime($startTime)
                  ->setEndTime($endTime);
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

        // Cargar disponibilidades indexadas por equipo → fecha → lista de rangos (minutos)
        $slots = []; // [teamId][dateKey] => [['start' => int, 'end' => int], ...]
        foreach ($teams as $team) {
            $avails = $availabilityRepository->findBy(['team' => $team, 'tournament' => $tournament]);
            foreach ($avails as $a) {
                $dateKey = $a->getDate()->format('Y-m-d');
                $s = (int)$a->getStartTime()->format('H') * 60 + (int)$a->getStartTime()->format('i');
                $e = $a->getEndTime()
                    ? (int)$a->getEndTime()->format('H') * 60 + (int)$a->getEndTime()->format('i')
                    : $s;
                $slots[$team->getId()][$dateKey][] = ['start' => $s, 'end' => $e];
            }
        }

        // Enfrentamientos ya disputados en este torneo
        $playedPairs = [];
        $games = $gameRepository->findBy(['tournament' => $tournament]);
        foreach ($games as $game) {
            if ($game->getTeam1() && $game->getTeam2()) {
                $a = min($game->getTeam1()->getId(), $game->getTeam2()->getId());
                $b = max($game->getTeam1()->getId(), $game->getTeam2()->getId());
                $playedPairs["{$a}-{$b}"] = true;
            }
        }

        // Cruce: para cada par que no haya jugado entre sí, buscar horas de inicio válidas
        $suggestions = [];
        $teamsArr = array_values($teams);
        for ($i = 0; $i < count($teamsArr); $i++) {
            for ($j = $i + 1; $j < count($teamsArr); $j++) {
                $t1 = $teamsArr[$i];
                $t2 = $teamsArr[$j];

                $a = min($t1->getId(), $t2->getId());
                $b = max($t1->getId(), $t2->getId());
                if (isset($playedPairs["{$a}-{$b}"])) continue;

                $s1 = $slots[$t1->getId()] ?? [];
                $s2 = $slots[$t2->getId()] ?? [];

                // Fechas que ambos equipos tienen disponibilidad
                $commonDates = array_keys(array_intersect_key($s1, $s2));

                $common = [];
                foreach ($commonDates as $dateKey) {
                    foreach ($s1[$dateKey] as $r1) {
                        foreach ($s2[$dateKey] as $r2) {
                            $overlapStart = max($r1['start'], $r2['start']);
                            $overlapEnd   = min($r1['end'],   $r2['end']);
                            if ($overlapStart <= $overlapEnd) {
                                // Generar huecos cada 30 min dentro del solapamiento
                                for ($t = $overlapStart; $t <= $overlapEnd; $t += 30) {
                                    $h = intdiv($t, 60);
                                    $m = $t % 60;
                                    $common[] = $dateKey . ' ' . sprintf('%02d:%02d', $h, $m);
                                }
                            }
                        }
                    }
                }

                if (!empty($common)) {
                    sort($common);
                    $suggestions[] = [
                        'team1' => $t1,
                        'team2' => $t2,
                        'slots' => array_values(array_unique($common)),
                    ];
                }
            }
        }

        usort($suggestions, fn($a, $b) => $a['slots'][0] <=> $b['slots'][0]);

        // Parejas con disponibilidad pero sin ningún cruce posible
        $teamsInSuggestions = [];
        foreach ($suggestions as $s) {
            $teamsInSuggestions[$s['team1']->getId()] = true;
            $teamsInSuggestions[$s['team2']->getId()] = true;
        }

        $strandedTeams = [];
        foreach ($teams as $team) {
            $hasSlots     = !empty($slots[$team->getId()]);
            $inSuggestion = isset($teamsInSuggestions[$team->getId()]);
            if ($hasSlots && !$inSuggestion) {
                $strandedTeams[] = $team;
            }
        }

        return $this->render('admin/availability/cross.html.twig', [
            'tournament'    => $tournament,
            'suggestions'   => $suggestions,
            'strandedTeams' => $strandedTeams,
        ]);
    }

    #[Route('/create-game', name: 'app_admin_availability_create_game', methods: ['POST'])]
    public function createGame(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $em,
        TeamRepository $teamRepository
    ): Response {
        $team1Id = $request->request->get('team1_id');
        $team2Id = $request->request->get('team2_id');
        $slot    = $request->request->get('slot');     // "2025-01-13 17:00"
        $phase   = trim($request->request->get('phase', ''));
        $court   = trim($request->request->get('court', ''));

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
