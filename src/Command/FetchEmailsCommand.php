<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Mail\EmailFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mail:fetch',
    description: 'Fetch inbound emails from configured IMAP mailboxes and store matched messages',
)]
final class FetchEmailsCommand extends Command
{
    public function __construct(
        private readonly EmailFetcher $emailFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('mailbox-id', null, InputOption::VALUE_REQUIRED, 'Fetch only one mailbox by ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->emailFetcher->isSupported()) {
            $io->error('PHP IMAP extension is not installed. Install php-imap to use this command.');

            return Command::FAILURE;
        }

        $mailboxId = $input->getOption('mailbox-id');
        $parsedMailboxId = is_numeric($mailboxId) ? (int) $mailboxId : null;

        try {
            $result = $this->emailFetcher->fetchAll($parsedMailboxId);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Imported: %d, skipped: %d',
            $result['imported'],
            $result['skipped'],
        ));

        foreach ($result['errors'] as $error) {
            $io->warning($error);
        }

        return $result['errors'] === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
