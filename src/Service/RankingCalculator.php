<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\Game;

class RankingCalculator
{
    public function calculate(Tournament $tournament): array
    {
        $ranking = [];

        foreach ($tournament->getGames() as $game) {
            // Solo contamos partidos que tengan resultado y AMBAS parejas asignadas
            if (!$game->getScore() || !$game->getTeam1() || !$game->getTeam2()) {
                continue;
            }

            $res = $this->parseDetailedScore($game->getScore());

            // Generamos el nombre de la pareja para usarlo como clave en el array
            $teamAName = $game->getTeam1()->getPlayer1()->getName() . ' / ' . $game->getTeam1()->getPlayer2()->getName();
            $teamBName = $game->getTeam2()->getPlayer1()->getName() . ' / ' . $game->getTeam2()->getPlayer2()->getName();

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

        // Calcular Diferencia (DF) y Ordenar
        foreach ($ranking as $name => &$data) {
            $data['df'] = $data['pf'] - $data['pc'];
            $data['team_name'] = $name; // Guardamos el nombre para usarlo en la tabla
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
        // Limpiamos el score: aceptamos tanto comas como espacios como separadores de sets
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
