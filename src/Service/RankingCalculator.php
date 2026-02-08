<?php

namespace App\Service;

use App\Entity\Tournament;

class RankingCalculator
{
    public function calculate(Tournament $tournament): array
    {
        $ranking = [];

        foreach ($tournament->getGames() as $game) {
            if (!$game->getScore()) continue;

            $res = $this->parseDetailedScore($game->getScore());

            $teamA = $game->getPlayer1()->getName() . ' / ' . $game->getPlayer2()->getName();
            $teamB = $game->getPlayer3()->getName() . ' / ' . $game->getPlayer4()->getName();

            $this->initTeam($ranking, $teamA);
            $this->initTeam($ranking, $teamB);

            // Partidos Jugados
            $ranking[$teamA]['pj']++;
            $ranking[$teamB]['pj']++;

            // Juegos (PF y PC)
            $ranking[$teamA]['pf'] += $res['gamesA'];
            $ranking[$teamA]['pc'] += $res['gamesB'];
            $ranking[$teamB]['pf'] += $res['gamesB'];
            $ranking[$teamB]['pc'] += $res['gamesA'];

            // Puntos y Ganados/Perdidos/Empatados
            if ($res['setsA'] > $res['setsB']) {
                $ranking[$teamA]['points'] += 3;
                $ranking[$teamA]['pg']++;
                $ranking[$teamB]['pp']++;
            } elseif ($res['setsB'] > $res['setsA']) {
                $ranking[$teamB]['points'] += 3;
                $ranking[$teamB]['pg']++;
                $ranking[$teamA]['pp']++;
            } else {
                $ranking[$teamA]['points'] += 1;
                $ranking[$teamB]['points'] += 1;
                $ranking[$teamA]['pe']++;
                $ranking[$teamB]['pe']++;
            }
        }

        // Calcular Diferencia (DF) y Ordenar
        foreach ($ranking as $name => &$data) {
            $data['df'] = $data['pf'] - $data['pc'];
        }

        // Ordenar: 1º Puntos, 2º Diferencia de Juegos
        uasort($ranking, function($a, $b) {
            if ($b['points'] !== $a['points']) return $b['points'] <=> $a['points'];
            return $b['df'] <=> $a['df'];
        });

        return $ranking;
    }

    private function parseDetailedScore(string $score): array
    {
        $sets = explode(',', $score);
        $setsA = 0; $setsB = 0;
        $gamesA = 0; $gamesB = 0;

        foreach ($sets as $set) {
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
