<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\Game;

class RankingCalculator
{
    public function calculate(Tournament $tournament): array
    {
        $ranking = [];

        // --- 1. INICIALIZACIÓN: Registramos a todas las parejas del torneo con 0 ---
        foreach ($tournament->getTeams() as $team) {
            $teamName = $team->getPlayer1()->getName() . ' / ' . ($team->getPlayer2() ? $team->getPlayer2()->getName() : 'TBD');
            $this->initTeam($ranking, $teamName);
        }

        // --- 2. PROCESO DE PARTIDOS: Sumamos estadísticas solo de los jugados ---
        foreach ($tournament->getGames() as $game) {
            // Solo contamos partidos que tengan resultado y AMBAS parejas asignadas
            if (!$game->getScore() || !$game->getTeam1() || !$game->getTeam2()) {
                continue;
            }

            $res = $this->parseDetailedScore($game->getScore());

            // Generamos el nombre de la pareja (debe coincidir con el formato de arriba)
            $teamAName = $game->getTeam1()->getPlayer1()->getName() . ' / ' . ($game->getTeam1()->getPlayer2() ? $game->getTeam1()->getPlayer2()->getName() : 'TBD');
            $teamBName = $game->getTeam2()->getPlayer1()->getName() . ' / ' . ($game->getTeam2()->getPlayer2() ? $game->getTeam2()->getPlayer2()->getName() : 'TBD');

            // Aseguramos que existan (por si se inscribieron después)
            $this->initTeam($ranking, $teamAName);
            $this->initTeam($ranking, $teamBName);

            // Partidos Jugados
            $ranking[$teamAName]['pj']++;
            $ranking[$teamBName]['pj']++;

            // Juegos (PF y PC)
            $ranking[$teamAName]['pf'] += $res['gamesA'];
            $ranking[$teamAName]['pc'] += $res['gamesB'];
            $ranking[$teamBName]['pf'] += $res['gamesB'];
            $ranking[$teamBName]['pc'] += $res['gamesA'];

            // Puntos y Ganados/Perdidos/Empatados
            if ($res['setsA'] > $res['setsB']) {
                $ranking[$teamAName]['points'] += 3;
                $ranking[$teamAName]['pg']++;
                $ranking[$teamBName]['pp']++;
            } elseif ($res['setsB'] > $res['setsA']) {
                $ranking[$teamBName]['points'] += 3;
                $ranking[$teamBName]['pg']++;
                $ranking[$teamAName]['pp']++;
            } else {
                $ranking[$teamAName]['points'] += 1;
                $ranking[$teamBName]['points'] += 1;
                $ranking[$teamAName]['pe']++;
                $ranking[$teamBName]['pe']++;
            }
        }

        // --- 3. CÁLCULO FINAL Y ORDENACIÓN ---
        foreach ($ranking as $name => &$data) {
            $data['df'] = $data['pf'] - $data['pc'];
        }

        // Ordenar: 1º Puntos, 2º Diferencia de Juegos, 3º Juegos a Favor
        uasort($ranking, function($a, $b) {
            if ($b['points'] !== $a['points']) return $b['points'] <=> $a['points'];
            if ($b['df'] !== $a['df']) return $b['df'] <=> $a['df'];
            return $b['pf'] <=> $a['pf'];
        });

        return $ranking;
    }

    private function parseDetailedScore(string $score): array
    {
        $cleanScore = str_replace([' ', '/', ';'], ',', $score);
        $sets = explode(',', $cleanScore);

        $setsA = 0; $setsB = 0;
        $gamesA = 0; $gamesB = 0;

        foreach ($sets as $set) {
            if (empty(trim($set))) continue;

            $parts = explode('-', trim($set));
            if (count($parts) === 2) {
                $gA = (int)$parts[0];
                $gB = (int)$parts[1];
                $gamesA += $gA;
                $gamesB += $gB;

                if ($gA > $gB) $setsA++;
                elseif ($gB > $gA) $setsB++;
            }
        }

        return ['setsA' => $setsA, 'setsB' => $setsB, 'gamesA' => $gamesA, 'gamesB' => $gamesB];
    }

    private function initTeam(&$ranking, $teamName): void
    {
        if (!isset($ranking[$teamName])) {
            $ranking[$teamName] = [
                'points' => 0, 'pj' => 0, 'pg' => 0, 'pp' => 0, 'pe' => 0,
                'pf' => 0, 'pc' => 0, 'df' => 0
            ];
        }
    }
}
