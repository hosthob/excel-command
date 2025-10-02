<?php

namespace App\Command;

use App\Service\CsvReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:show-csv',
    description: 'Display CSV file contents in a formatted table',
)]
class ShowCsvCommand extends Command
{
    private CsvReader $csvReader;
    private ?SymfonyStyle $io = null;
    private ?string $filePath = null;
    private int $limit = 10;
    private bool $headersOnly = false;
    private bool $countOnly = false;
    private array $rows = [];
    private array $headers = [];

    public function __construct(CsvReader $csvReader)
    {
        $this->csvReader = $csvReader;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of rows to display', 10)
            ->addOption('headers', null, InputOption::VALUE_NONE, 'Show only headers')
            ->addOption('count', 'c', InputOption::VALUE_NONE, 'Show only row count')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeProperties($input, $output);

        try {
            if ($this->countOnly) {
                return $this->showRowCount();
            }

            if ($this->headersOnly) {
                return $this->showHeaders();
            }

            return $this->showCsvTable();

        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function initializeProperties(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->filePath = $input->getArgument('file');
        $this->limit = (int) $input->getOption('limit');
        $this->headersOnly = $input->getOption('headers');
        $this->countOnly = $input->getOption('count');
    }

    private function showRowCount(): int
    {
        $count = $this->csvReader->countRows($this->filePath);
        $this->io->success("Total rows in {$this->filePath}: {$count}");
        return Command::SUCCESS;
    }

    private function showHeaders(): int
    {
        $this->headers = $this->csvReader->getHeaders($this->filePath);
        $this->io->title('CSV Headers');
        $this->io->listing($this->headers);
        return Command::SUCCESS;
    }

    private function showCsvTable(): int
    {
        $this->rows = $this->csvReader->read($this->filePath);
        
        if (empty($this->rows)) {
            $this->io->warning('CSV file is empty');
            return Command::SUCCESS;
        }

        $this->limitRowsIfNeeded();
        $this->renderTable();

        return Command::SUCCESS;
    }

    private function limitRowsIfNeeded(): void
    {
        if ($this->limit > 0 && count($this->rows) > $this->limit) {
            $totalRows = count($this->rows);
            $this->rows = array_slice($this->rows, 0, $this->limit);
            $this->io->note("Showing first {$this->limit} rows of {$totalRows} total rows");
        }
    }

    private function renderTable(): void
    {
        $table = $this->io->createTable();
        
        if (!empty($this->rows)) {
            $table->setHeaders($this->rows[0]);
            
            // Add data rows (skip first row as it's headers)
            for ($i = 1; $i < count($this->rows); $i++) {
                $table->addRow($this->rows[$i]);
            }
        }

        $this->io->title("CSV File: {$this->filePath}");
        $table->render();
    }
}