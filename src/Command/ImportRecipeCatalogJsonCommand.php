<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Recipe\RecipeCatalogJsonImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recipe:import-json',
    description: 'Import recipe catalog from nested JSON file',
)]
final class ImportRecipeCatalogJsonCommand extends Command
{
    public function __construct(
        private readonly RecipeCatalogJsonImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to recipe-catalog JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');

        try {
            $result = $this->importer->importFromFile($file);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Import finished: created %d, updated %d.',
            $result->recipesCreated,
            $result->recipesUpdated,
        ));

        if ($result->recipeIds !== []) {
            $io->listing(array_map(static fn (int $id) => (string) $id, $result->recipeIds));
        }

        foreach ($result->errors as $error) {
            $io->warning($error);
        }

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
