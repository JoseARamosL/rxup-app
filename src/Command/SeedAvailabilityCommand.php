<?php

namespace App\Command;

use App\Entity\Availability;
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

#[AsCommand(name: 'app:seed-availability', description: 'Crea una liga de prueba con 10 parejas y disponibilidades')]
class SeedAvailabilityCommand extends Command
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

        if ($this->em->getRepository(Tournament::class)->findOneBy(['name' => 'Liga Disponibilidades 2025'])) {
            $io->warning('Ya existe esta liga de prueba.');
            return Command::SUCCESS;
        }

        // --- 20 jugadores nuevos ---
        $playerData = [
            ['Sergio Navarro',   'sergio.navarro@rxup.es',   '622200001'],
            ['Marta Ruiz',       'marta.ruiz@rxup.es',       '622200002'],
            ['Raúl Herrera',     'raul.herrera@rxup.es',     '622200003'],
            ['Sofía Castro',     'sofia.castro@rxup.es',     '622200004'],
            ['Álvaro Jiménez',   'alvaro.jimenez@rxup.es',   '622200005'],
            ['Elena Vega',       'elena.vega@rxup.es',       '622200006'],
            ['Marcos Ortega',    'marcos.ortega@rxup.es',    '622200007'],
            ['Cristina Gil',     'cristina.gil@rxup.es',     '622200008'],
            ['Rubén Molina',     'ruben.molina@rxup.es',     '622200009'],
            ['Patricia Serrano', 'patricia.serrano@rxup.es', '622200010'],
            ['Diego Muñoz',      'diego.munoz@rxup.es',      '622200011'],
            ['Natalia Ibáñez',   'natalia.ibanez@rxup.es',   '622200012'],
            ['Iván Romero',      'ivan.romero@rxup.es',      '622200013'],
            ['Claudia Flores',   'claudia.flores@rxup.es',   '622200014'],
            ['Adrián Guerrero',  'adrian.guerrero@rxup.es',  '622200015'],
            ['Sandra Peña',      'sandra.pena@rxup.es',      '622200016'],
            ['Borja Santos',     'borja.santos@rxup.es',     '622200017'],
            ['Verónica Ríos',    'veronica.rios@rxup.es',    '622200018'],
            ['Guillermo Cano',   'guillermo.cano@rxup.es',   '622200019'],
            ['Laura Campos',     'laura.campos@rxup.es',     '622200020'],
        ];

        $userRepo = $this->em->getRepository(User::class);
        $players = [];
        foreach ($playerData as [$name, $email, $phone]) {
            $u = $userRepo->findOneBy(['email' => $email]);
            if (!$u) {
                $u = new User();
                $u->setName($name)->setEmail($email)->setPhone($phone)
                  ->setRoles(['ROLE_USER'])->setPassword('NO_PASSWORD');
                $this->em->persist($u);
            }
            $players[] = $u;
        }
        $this->em->flush();
        $io->success('20 jugadores listos.');

        // --- Liga ---
        $liga = new Tournament();
        $liga->setName('Liga Disponibilidades 2025')
             ->setType('LEAGUE')
             ->setHasRanking(true)
             ->setActive(true)
             ->setDescription('Liga de prueba para el sistema de disponibilidades.');
        $this->em->persist($liga);
        $this->em->flush();

        // --- 10 parejas ---
        $pairs = [
            [$players[0],  $players[1]],   // P1:  Sergio / Marta
            [$players[2],  $players[3]],   // P2:  Raúl / Sofía
            [$players[4],  $players[5]],   // P3:  Álvaro / Elena
            [$players[6],  $players[7]],   // P4:  Marcos / Cristina
            [$players[8],  $players[9]],   // P5:  Rubén / Patricia
            [$players[10], $players[11]],  // P6:  Diego / Natalia
            [$players[12], $players[13]],  // P7:  Iván / Claudia
            [$players[14], $players[15]],  // P8:  Adrián / Sandra
            [$players[16], $players[17]],  // P9:  Borja / Verónica  ← sin cruce
            [$players[18], $players[19]],  // P10: Guillermo / Laura ← sin cruce
        ];

        $teams = [];
        foreach ($pairs as [$p1, $p2]) {
            $t = new Team();
            $t->setTournament($liga)->setPlayer1($p1)->setPlayer2($p2);
            $this->em->persist($t);
            $teams[] = $t;
        }
        $this->em->flush();
        $io->success('10 parejas creadas.');

        // --- Disponibilidades (semana 2026-10-12 al 2026-10-18) como rangos ---
        // Lunes=12, Martes=13, Miércoles=14, Jueves=15, Viernes=16, Sábado=17, Domingo=18
        $mon = '2026-10-12';
        $tue = '2026-10-13';
        $wed = '2026-10-14';
        $thu = '2026-10-15';
        $fri = '2026-10-16';
        $sat = '2026-10-17';
        $sun = '2026-10-18';

        // P1 (Sergio/Marta):   Lun 17-20, Mié 18-21, Vie 16-19
        $this->addRanges($teams[0], $liga, [[$mon,'17:00','20:00'], [$wed,'18:00','21:00'], [$fri,'16:00','19:00']]);

        // P2 (Raúl/Sofía):     Lun 17-20, Jue 19-21
        $this->addRanges($teams[1], $liga, [[$mon,'17:00','20:00'], [$thu,'19:00','21:00']]);

        // P3 (Álvaro/Elena):   Mié 18-21, Vie 16-19, Sáb 09-12
        $this->addRanges($teams[2], $liga, [[$wed,'18:00','21:00'], [$fri,'16:00','19:00'], [$sat,'09:00','12:00']]);

        // P4 (Marcos/Cristina): Lun 17-20, Mié 18-21
        $this->addRanges($teams[3], $liga, [[$mon,'17:00','20:00'], [$wed,'18:00','21:00']]);

        // P5 (Rubén/Patricia): Jue 19-21, Vie 16-19
        $this->addRanges($teams[4], $liga, [[$thu,'19:00','21:00'], [$fri,'16:00','19:00']]);

        // P6 (Diego/Natalia):  Sáb 09-12, Dom 10-13
        $this->addRanges($teams[5], $liga, [[$sat,'09:00','12:00'], [$sun,'10:00','13:00']]);

        // P7 (Iván/Claudia):   Lun 17-20, Vie 16-19
        $this->addRanges($teams[6], $liga, [[$mon,'17:00','20:00'], [$fri,'16:00','19:00']]);

        // P8 (Adrián/Sandra):  Mié 18-21, Sáb 09-12
        $this->addRanges($teams[7], $liga, [[$wed,'18:00','21:00'], [$sat,'09:00','12:00']]);

        // P9 (Borja/Verónica):  Dom 07-09 ← no coincide con P6 Dom 10-13
        $this->addRanges($teams[8], $liga, [[$sun,'07:00','09:00']]);

        // P10 (Guillermo/Laura): Mar 21-23 ← nadie más tiene disponibilidad en martes
        $this->addRanges($teams[9], $liga, [[$tue,'21:00','23:00']]);

        $this->em->flush();
        $io->success('Disponibilidades creadas.');
        $io->success('¡Liga de prueba lista! Entra en /admin/tournament y busca "Liga Disponibilidades 2025".');

        return Command::SUCCESS;
    }

    private function addRanges(Team $team, Tournament $tournament, array $ranges): void
    {
        foreach ($ranges as [$dateStr, $startStr, $endStr]) {
            $a = new Availability();
            $a->setTeam($team)
              ->setTournament($tournament)
              ->setDate(\DateTime::createFromFormat('Y-m-d', $dateStr))
              ->setStartTime(\DateTime::createFromFormat('H:i', $startStr))
              ->setEndTime(\DateTime::createFromFormat('H:i', $endStr));
            $this->em->persist($a);
        }
    }
}
