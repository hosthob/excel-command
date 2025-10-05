<?php

namespace App\Command;

use App\Service\ExcelExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-excel',
    description: 'Export CSV to Excel with a bar chart',
)]
class ExportExcelCommand extends Command
{
    private ExcelExporter $excelExporter;
    private ?SymfonyStyle $io = null;

    public function __construct(ExcelExporter $excelExporter)
    {
        $this->excelExporter = $excelExporter;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csv', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addArgument('output', InputArgument::REQUIRED, 'Path to output XLSX file')
            ->addOption('category-col', null, InputOption::VALUE_OPTIONAL, '1-based column index for categories (X axis)', 1)
            ->addOption('value-col', null, InputOption::VALUE_OPTIONAL, '1-based column index for values (Y axis)', 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $csv = (string) $input->getArgument('csv');
        $outputXlsx = (string) $input->getArgument('output');
        $categoryCol = (int) $input->getOption('category-col');
        $valueCol = (int) $input->getOption('value-col');

        try {
            $this->excelExporter->exportWithChart($csv, $outputXlsx, $categoryCol, $valueCol);
            $this->io->success("Excel written with chart: {$outputXlsx}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}


