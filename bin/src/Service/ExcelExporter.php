<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;

class ExcelExporter
{
    private CsvReader $csvReader;
    private ?Spreadsheet $spreadsheet = null;
    private ?Worksheet $sheet = null;
    private array $rows = [];
    private int $rowCount = 0;
    private int $categoryCol = 1;
    private int $valueCol = 2;

    public function __construct(CsvReader $csvReader)
    {
        $this->csvReader = $csvReader;
    }

    /**
     * Create an Excel file from a CSV and insert a chart.
     */
    public function exportWithChart(string $csvPath, string $outputXlsx, ?int $categoryCol = null, ?int $valueCol = null): void
    {
        // read the csv file and get the rows
        $this->rows = $this->csvReader->read($csvPath);
        if (empty($this->rows)) {
            throw new \RuntimeException('CSV is empty, nothing to export.');
        }

        // set the category and value columns (auto-detect by header if possible)
        $headers = $this->rows[0];
        $detectedCategory = $this->detectColumnIndex($headers, ['name']);
        $detectedValue = $this->detectColumnIndex($headers, ['income', 'amount', 'salary', 'value']);

        $this->categoryCol = $categoryCol ?? ($detectedCategory ?: 1);
        $this->valueCol = $valueCol ?? ($detectedValue ?: 2);

        // create a new spreadsheet
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Data');

        // Write all CSV rows to the worksheet
        $rowIndex = 1;
        foreach ($this->rows as $row) {
            $colIndex = 1;
            foreach ($row as $cell) {
                $cellAddress = $this->columnIndexToLetter($colIndex) . $rowIndex;
                $this->sheet->setCellValue($cellAddress, $cell);
                $colIndex++;
            }
            $rowIndex++;
        }

        $this->rowCount = count($this->rows);

        // Build chart ranges (A2:A{n} for categories; B2:B{n} for values by default)
        $categoryColLetter = $this->columnIndexToLetter($this->categoryCol);
        $valueColLetter = $this->columnIndexToLetter($this->valueCol);
        $categoryRange = 'Data!$' . $categoryColLetter . '$2:$' . $categoryColLetter . '$' . $this->rowCount;
        $valueRange = 'Data!$' . $valueColLetter . '$2:$' . $valueColLetter . '$' . $this->rowCount;

        $seriesLabelRef = 'Data!$' . $valueColLetter . '$1';
        $seriesLabels = [
            new DataSeriesValues('String', $seriesLabelRef, null, 1),
        ];
        $xAxisTickValues = [
            new DataSeriesValues('String', $categoryRange, null, $this->rowCount - 1),
        ];
        $dataSeriesValues = [
            new DataSeriesValues('Number', $valueRange, null, $this->rowCount - 1),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART, // Column/bar chart
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($dataSeriesValues) - 1),
            $seriesLabels,
            $xAxisTickValues,
            $dataSeriesValues
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        $titleText = 'Chart';
        if (isset($headers[$this->valueCol - 1])) {
            $titleText = (string) $headers[$this->valueCol - 1] . ' by ' . ($headers[$this->categoryCol - 1] ?? 'Category');
        }

        $title = new Title($titleText);
        $yAxisLabel = new Title('Value');

        $chart = new Chart(
            'chart1',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            $yAxisLabel
        );

        // Position chart on the worksheet
        $chart->setTopLeftPosition('D2');
        $chart->setBottomRightPosition('L20');

        $this->sheet->addChart($chart);

        $writer = new Xlsx($this->spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($outputXlsx);
    }

    private function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }

    private function detectColumnIndex(array $headers, array $candidates): ?int
    {
        $lowerHeaders = array_map(static function ($h) { return is_string($h) ? strtolower(trim($h)) : ''; }, $headers);
        foreach ($candidates as $candidate) {
            $idx = array_search(strtolower($candidate), $lowerHeaders, true);
            if ($idx !== false) {
                return $idx + 1; // convert to 1-based index
            }
        }
        return null;
    }
}


