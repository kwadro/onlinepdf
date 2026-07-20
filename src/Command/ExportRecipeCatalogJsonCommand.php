<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Recipe\RecipeCatalogJsonExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recipe:export-json',
    description: 'Export recipe catalog to nested JSON file',
)]
final class ExportRecipeCatalogJsonCommand extends Command
{
    public function __construct(
        private readonly RecipeCatalogJsonExporter $exporter,
        private readonly string $appDomain,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Output JSON file', 'export/recipe-catalog.json')
            ->addOption('site', null, InputOption::VALUE_REQUIRED, 'Site domain')
            ->addOption('recipe-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Export only selected recipe ids')
            ->addOption('without-reference-data', null, InputOption::VALUE_NONE, 'Skip reference_data section');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');
        $site = (string) ($input->getOption('site') ?: $this->appDomain);
        $recipeIds = array_map('intval', $input->getOption('recipe-id') ?? []);

        try {
            $this->exporter->exportToFile(
                $file,
                $site,
                $recipeIds === [] ? null : $recipeIds,
                !$input->getOption('without-reference-data'),
            );
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Recipe catalog exported to %s', $file));

        return Command::SUCCESS;
    }
}
