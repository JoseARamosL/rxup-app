<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findDuplicate(\App\Entity\Tournament $tournament, \App\Entity\Team $team1, \App\Entity\Team $team2): ?Game
    {
        return $this->createQueryBuilder('g')
            ->where('g.tournament = :tournament')
            ->andWhere('(g.team1 = :t1 AND g.team2 = :t2) OR (g.team1 = :t2 AND g.team2 = :t1)')
            ->setParameter('tournament', $tournament)
            ->setParameter('t1', $team1)
            ->setParameter('t2', $team2)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
