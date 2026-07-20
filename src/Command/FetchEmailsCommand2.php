<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\EmailMessage;
use App\Repository\EmailMailboxRepository;
use App\Service\Mail\EmailFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mail:fetch2',
    description: 'Fetch inbound emails from configured IMAP mailboxes and store matched messages',
)]
final class FetchEmailsCommand2 extends Command
{
    public function __construct(
        private readonly EmailFetcher $emailFetcher,
        private readonly EmailMailboxRepository $emailMailboxRepository,
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

        $parsedMailboxId = 1;

        try {
            $mailbox =$this->emailMailboxRepository->findOneBy(['id' => $parsedMailboxId]);
            $result = $this->emailFetcher->fetchMailbox($mailbox);

            $result = $this->emailFetcher->fetchMailbox($mailbox,'[Gmail]/Sent Mail');

        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Imported: %d, skipped: %d',
            $result['imported'],
            $result['skipped'],
        ));
        if(isset($result['errors'])){
            foreach ($result['errors'] as $error) {
                $io->warning($error);
            }
        }


        return !isset($result['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

}
