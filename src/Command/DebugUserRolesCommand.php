<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:debug-user-roles')]
class DebugUserRolesCommand extends Command
{
    public function __construct(private UserRepository $userRepo)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->userRepo->findOneBy(['email' => $input->getArgument('email')]);
        if (!$user) {
            $output->writeln('<error>Usuario no encontrado</error>');
            return Command::FAILURE;
        }

        $output->writeln("ID: {$user->getId()}");
        $output->writeln("Email: {$user->getEmail()}");
        $output->writeln("isInternal: " . ($user->isInternal() ? 'true' : 'false'));
        $output->writeln("getRoles(): " . implode(', ', $user->getRoles()));
        $output->writeln("UserRoles collection count: " . $user->getUserRoles()->count());

        foreach ($user->getUserRoles() as $ur) {
            $output->writeln("  UserRole id={$ur->getId()} role={$ur->getRole()->getName()} deleted=" . ($ur->getDeletedAt() ? 'SI' : 'no'));
        }

        return Command::SUCCESS;
    }
}
