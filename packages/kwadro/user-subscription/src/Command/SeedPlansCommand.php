<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Command;

use Kwadro\UserSubscription\Service\PlanSeeder;
use Kwadro\UserSubscription\Service\SubscriptionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'subscription:plans:seed',
    description: 'Seed default subscription plans from bundle configuration.',
)]
final class SeedPlansCommand extends Command
{
    public function __construct(
        private PlanSeeder $planSeeder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Update existing plans from config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = $this->planSeeder->seed((bool) $input->getOption('update'));

        $io->success(sprintf('Subscription plans seeded (%d new).', $created));

        return Command::SUCCESS;
    }
}
