<?php

namespace App\Command;

use App\Service\HebraicCalendarService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:daily-badge-check',
    description: 'Vérifie les dates spéciales et attribue les badges correspondants.',
)]
class DailyBadgeCheckCommand extends Command
{
    public function __construct(
        private HebraicCalendarService $calendarService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Vérification des badges du jour...');

        $this->calendarService->checkAndAwardDailyBadges();

        $io->success('Vérification terminée et badges attribués si nécessaire !');

        return Command::SUCCESS;
    }
}
