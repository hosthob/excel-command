<?php

namespace App\Service;

class CsvReader
{
    /**
     * Read CSV file and return array of rows
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $rows = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Get the number of rows in CSV file
     *
     * @param string $filePath
     * @return int
     */
    public function countRows(string $filePath): int
    {
        $rows = $this->read($filePath);
        return count($rows);
    }

    /**
     * Get headers (first row) from CSV file
     *
     * @param string $filePath
     * @return array
     */
    public function getHeaders(string $filePath): array
    {
        $rows = $this->read($filePath);
        return $rows[0] ?? [];
    }
}
