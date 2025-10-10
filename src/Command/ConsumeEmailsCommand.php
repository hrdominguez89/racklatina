<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:messenger:consume-emails',
    description: 'Consume emails from the messenger queue and send them via Microsoft Graph API'
)]
class ConsumeEmailsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Racklatina - Email Queue Consumer');
        $io->info('Starting to consume emails from the queue...');
        $io->warning('This command will run messenger:consume in the background.');
        $io->newLine();
        
        $io->note([
            'This is a wrapper command.',
            'You should run: php bin/console messenger:consume async -vv',
            'Or use this command in production with supervisor or systemd.'
        ]);

        return Command::SUCCESS;
    }
}
