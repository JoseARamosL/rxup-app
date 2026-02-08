<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-users', description: 'Crea un admin y un usuario de prueba')]
class CreateUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Crear Admin
        $admin = new User();
        $admin->setEmail('admin@padel.com');
        $admin->setPhone('600000001');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $this->entityManager->persist($admin);

        // 2. Crear Jugador Normal
        $user = new User();
        $user->setEmail('jugador@padel.com');
        $user->setPhone('600000002');
        $user->setRoles(['ROLE_USER']);
        // Importante: La contraseña para el usuario normal debe coincidir
        // con el valor dummy que pusimos en el JS (nopassword)
        $user->setPassword($this->passwordHasher->hashPassword($user, 'nopassword'));
        $this->entityManager->persist($user);

        $user = new User();
        $user->setEmail('jugador2@padel.com');
        $user->setPhone('600000003');
        $user->setRoles(['ROLE_USER']);
        // Importante: La contraseña para el usuario normal debe coincidir
        // con el valor dummy que pusimos en el JS (nopassword)
        $user->setPassword($this->passwordHasher->hashPassword($user, 'nopassword'));
        $this->entityManager->persist($user);

        $user = new User();
        $user->setEmail('jugador3@padel.com');
        $user->setPhone('600000004');
        $user->setRoles(['ROLE_USER']);
        // Importante: La contraseña para el usuario normal debe coincidir
        // con el valor dummy que pusimos en el JS (nopassword)
        $user->setPassword($this->passwordHasher->hashPassword($user, 'nopassword'));
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
