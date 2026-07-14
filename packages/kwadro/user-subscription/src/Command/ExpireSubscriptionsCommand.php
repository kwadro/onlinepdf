<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Command;

use Kwadro\UserSubscription\Service\SubscriptionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'subscription:expire',
    description: 'Mark expired active subscriptions as expired.',
)]
final class ExpireSubscriptionsCommand extends Command
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->subscriptionManager->expireDueSubscriptions();

        $io->success(sprintf('Expired %d subscription(s).', $count));

        return Command::SUCCESS;
    }
}
