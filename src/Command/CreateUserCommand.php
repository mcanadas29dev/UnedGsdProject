<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Command\Command;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crea un nuevo usuario.')
            ->addArgument('email', InputArgument::REQUIRED, 'Email del usuario')
            ->addArgument('password', InputArgument::REQUIRED, 'Contraseña del usuario')
            ->addArgument('roles', InputArgument::OPTIONAL, 'Roles del usuario separados por coma', 'ROLE_USER');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');
        $rolesInput = $input->getArgument('roles');

        $roles = array_map('trim', explode(',', $rolesInput));

        // Comprueba que no exista el usuario ya
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $output->writeln('<error>Ya existe un usuario con ese email.</error>');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Usuario creado correctamente: ' . $email . '</info>');

        return Command::SUCCESS;
    }
}
