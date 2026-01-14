<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Add a short description for your command',
)]

class CreateUserCommand extends Command
{


    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Email del usuario')
            ->addArgument('password', InputArgument::REQUIRED, 'Contraseña del usuario');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('username');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setUsername($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln("Usuario $email creado con éxito.");

        return Command::SUCCESS;
    }
}
