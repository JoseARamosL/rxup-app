<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-data', description: 'Puebla la BDD con datos de prueba')]
class SeedDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Sembrando datos de prueba...');

        // --- JUGADORES (crea si no existe, recupera si ya existe) ---
        $playerData = [
            ['Carlos García',    'carlos.garcia@rxup.es',    '611100001'],
            ['Laura Martínez',   'laura.martinez@rxup.es',   '611100002'],
            ['Pablo Rodríguez',  'pablo.rodriguez@rxup.es',  '611100003'],
            ['Ana López',        'ana.lopez@rxup.es',        '611100004'],
            ['Miguel Fernández', 'miguel.fernandez@rxup.es', '611100005'],
            ['Carmen Sánchez',   'carmen.sanchez@rxup.es',   '611100006'],
            ['Javier González',  'javier.gonzalez@rxup.es',  '611100007'],
            ['Isabel Díaz',      'isabel.diaz@rxup.es',      '611100008'],
            ['Antonio Pérez',    'antonio.perez@rxup.es',    '611100009'],
            ['María Torres',     'maria.torres@rxup.es',     '611100010'],
            ['David Ramírez',    'david.ramirez@rxup.es',    '611100011'],
            ['Lucía Moreno',     'lucia.moreno@rxup.es',     '611100012'],
        ];

        $userRepo = $this->em->getRepository(User::class);
        $players = [];
        $created = 0;
        foreach ($playerData as [$name, $email, $phone]) {
            $u = $userRepo->findOneBy(['email' => $email]);
            if (!$u) {
                $u = new User();
                $u->setName($name)->setEmail($email)->setPhone($phone)->setRoles(['ROLE_USER']);
                $u->setPassword($this->passwordHasher->hashPassword($u, 'nopassword'));
                $this->em->persist($u);
                $created++;
            }
            $players[] = $u;
        }
        $this->em->flush();
        $io->success($created > 0 ? "$created jugadores creados." : 'Jugadores ya existían, recuperados.');

        // Alias cortos
        [$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9,$p10,$p11,$p12] = $players;

        // -------------------------------------------------------
        // TORNEO 1: Liga Verano 2024 — FINALIZADA (inactive)
        // -------------------------------------------------------
        $liga1 = $this->createTournament('Liga Verano 2024', 'LEAGUE', true, false,
            'Primera liga de la temporada. Formato todos contra todos. 4 parejas.');
        if ($liga1) {
            $t1a = $this->createTeam($liga1, $p1, $p2);
            $t1b = $this->createTeam($liga1, $p3, $p4);
            $t1c = $this->createTeam($liga1, $p5, $p6);
            $t1d = $this->createTeam($liga1, $p7, $p8);
            $this->createGame($liga1, $t1a, $t1b, 'Jornada 1', 'Pista 1', '2024-06-05 10:00', '6-3 6-2');
            $this->createGame($liga1, $t1c, $t1d, 'Jornada 1', 'Pista 2', '2024-06-05 12:00', '4-6 6-3 6-4');
            $this->createGame($liga1, $t1a, $t1c, 'Jornada 2', 'Pista 1', '2024-06-12 10:00', '6-4 6-1');
            $this->createGame($liga1, $t1b, $t1d, 'Jornada 2', 'Pista 2', '2024-06-12 12:00', '7-5 3-6 6-4');
            $this->createGame($liga1, $t1a, $t1d, 'Jornada 3', 'Pista 1', '2024-06-19 10:00', '6-2 6-3');
            $this->createGame($liga1, $t1b, $t1c, 'Jornada 3', 'Pista 2', '2024-06-19 12:00', '6-4 7-5');
            $this->em->flush();
            $io->success('Liga Verano 2024 creada (finalizada).');
        } else {
            $io->note('Liga Verano 2024 ya existe, omitida.');
        }

        $copa1 = $this->createTournament('Copa Otoño 2024', 'TOURNAMENT', false, false,
            'Torneo eliminatorio de otoño. Cuartos, semifinales y final.');
        if ($copa1) {
            $t2a = $this->createTeam($copa1, $p9,  $p10);
            $t2b = $this->createTeam($copa1, $p11, $p12);
            $t2c = $this->createTeam($copa1, $p1,  $p4);
            $t2d = $this->createTeam($copa1, $p5,  $p8);
            $this->createGame($copa1, $t2a, $t2c, 'Semifinal', 'Pista Central', '2024-10-10 10:00', '6-3 6-4');
            $this->createGame($copa1, $t2b, $t2d, 'Semifinal', 'Pista Central', '2024-10-10 12:30', '3-6 6-2 6-3');
            $this->createGame($copa1, $t2a, $t2b, 'Final',     'Pista Central', '2024-10-17 11:00', '7-5 6-4');
            $this->em->flush();
            $io->success('Copa Otoño 2024 creada (finalizada).');
        } else {
            $io->note('Copa Otoño 2024 ya existe, omitida.');
        }

        $liga2 = $this->createTournament('Liga Invierno 2025', 'LEAGUE', true, true,
            'Liga en curso. Quedan jornadas por disputar. ¡Aprieta la clasificación!');
        if ($liga2) {
            $t3a = $this->createTeam($liga2, $p3,  $p6);
            $t3b = $this->createTeam($liga2, $p7,  $p2);
            $t3c = $this->createTeam($liga2, $p9,  $p12);
            $t3d = $this->createTeam($liga2, $p11, $p10);
            $this->createGame($liga2, $t3a, $t3b, 'Jornada 1', 'Pista 1', '2025-01-08 10:00', '6-4 6-3');
            $this->createGame($liga2, $t3c, $t3d, 'Jornada 1', 'Pista 2', '2025-01-08 12:00', '6-2 7-5');
            $this->createGame($liga2, $t3a, $t3c, 'Jornada 2', 'Pista 1', '2025-01-15 10:00', '7-6 4-6 6-3');
            $this->createGame($liga2, $t3b, $t3d, 'Jornada 2', 'Pista 2', '2025-01-15 12:00', null);
            $this->createGame($liga2, $t3a, $t3d, 'Jornada 3', 'Pista 1', '2025-01-22 10:00', null);
            $this->createGame($liga2, $t3b, $t3c, 'Jornada 3', 'Pista 2', '2025-01-22 12:00', null);
            $this->em->flush();
            $io->success('Liga Invierno 2025 creada (en curso).');
        } else {
            $io->note('Liga Invierno 2025 ya existe, omitida.');
        }

        $open1 = $this->createTournament('Open Primavera 2025', 'TOURNAMENT', false, true,
            'Open de primavera. Formato eliminatorio directo. La final está por disputarse.');
        if ($open1) {
            $t4a = $this->createTeam($open1, $p1,  $p6);
            $t4b = $this->createTeam($open1, $p3,  $p8);
            $t4c = $this->createTeam($open1, $p5,  $p2);
            $t4d = $this->createTeam($open1, $p7,  $p4);
            $this->createGame($open1, $t4a, $t4b, 'Semifinal', 'Pista Central', '2025-03-15 10:00', '6-3 6-4');
            $this->createGame($open1, $t4c, $t4d, 'Semifinal', 'Pista Central', '2025-03-15 12:30', '4-6 6-3 7-5');
            $this->createGame($open1, $t4a, $t4c, 'Final',     'Pista Central', '2025-03-22 11:00', null);
            $this->em->flush();
            $io->success('Open Primavera 2025 creado (en curso).');
        } else {
            $io->note('Open Primavera 2025 ya existe, omitida.');
        }

        $io->success('¡Base de datos poblada con éxito!');
        return Command::SUCCESS;
    }

    private function createTournament(string $name, string $type, bool $hasRanking, bool $active, string $desc): ?Tournament
    {
        $existing = $this->em->getRepository(Tournament::class)->findOneBy(['name' => $name]);
        if ($existing) {
            return null;
        }
        $t = new Tournament();
        $t->setName($name)->setType($type)->setHasRanking($hasRanking)->setActive($active)->setDescription($desc);
        $this->em->persist($t);
        return $t;
    }

    private function createTeam(Tournament $tournament, User $p1, User $p2): Team
    {
        $team = new Team();
        $team->setTournament($tournament)->setPlayer1($p1)->setPlayer2($p2);
        $this->em->persist($team);
        return $team;
    }

    private function createGame(Tournament $tournament, Team $team1, Team $team2, string $phase, string $court, string $date, ?string $score): void
    {
        $game = new Game();
        $game->setTournament($tournament)
             ->setTeam1($team1)
             ->setTeam2($team2)
             ->setPhase($phase)
             ->setCourt($court)
             ->setDate(new \DateTime($date))
             ->setScore($score);
        $this->em->persist($game);
    }
}
