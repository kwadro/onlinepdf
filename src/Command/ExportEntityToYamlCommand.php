<?php

namespace App\Command;

use App\Service\YamlEntityExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export:yaml',
    description: 'Export Doctrine entity to Yaml'
)]
class ExportEntityToYamlCommand extends Command
{
    public function __construct(
        private readonly YamlEntityExporter $exporter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity FQCN or short name (e.g. Product)')
            ->addArgument('file', InputArgument::OPTIONAL, 'Output Yaml file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entity = $input->getArgument('entity');
        if(!is_dir('./export-yaml')){
            mkdir('./export-yaml', 0775, true);
        }

        $file   = $input->getArgument('file')
            ?? './export-yaml/'.strtolower($entity) . '.yaml';

        // allow short name
        if (!str_contains($entity, '\\')) {
            $entity = 'App\\Entity\\' . $entity;
        }

        $io->info("Exporting $entity to $file");

        try {
            $this->exporter->export($entity, $file);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Export finished successfully');

        return Command::SUCCESS;
    }
}

