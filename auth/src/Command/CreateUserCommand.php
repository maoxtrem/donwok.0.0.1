<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Domain\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Domain\Repository\UserRepositoryInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Add a short description for your command',
)]

class CreateUserCommand extends Command
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        if ($this->userRepository->existeConUsername('admin')) {
            $output->writeln('<info>El usuario admin ya existe</info>');
            return Command::SUCCESS;
        }

        $user = new User(
            username: 'admin',
            roles: ['ROLE_ADMIN']
        );

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'admin123'
        );

        $user->setPassword($hashedPassword);

        $this->userRepository->guardar($user);

        $output->writeln('<info>Usuario admin creado correctamente</info>');

        return Command::SUCCESS;
    }
}
