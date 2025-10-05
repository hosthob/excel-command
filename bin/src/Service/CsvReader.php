<?php

namespace App\Service;

/**
 * Service class for reading and processing CSV files
 * Handles file operations
 */
class CsvReader
{
    /**
     * Read CSV file and return array of rows
     *
     * @param string $filePath Path to the CSV file
     * @return array Array of rows, where each row is an array of column values
     * @throws \Exception If file doesn't exist or can't be read
     */
    public function read(string $filePath): array
    {
        // Check if file exists before trying to open it
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $rows = []; // Initialize empty array to store CSV rows
        
        // Open file in read mode and check if successful
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            // Read CSV line by line until end of file
            // 1000 is the max line length, "," is the delimiter
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data; // Add each row to our array
            }
            fclose($handle); // Always close the file handle
        }

        return $rows;
    }

    /**
     * Get the number of rows in CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @return int Number of rows in the CSV file
     */
    public function countRows(string $filePath): int
    {
        // Read all rows first, then count them
        $rows = $this->read($filePath);
        return count($rows);
    }

    /**
     * Get headers (first row) from CSV file
     * 
     * @param string $filePath Path to the CSV file
     * @return array Array of header column names, or empty array if file is empty
     */
    public function getHeaders(string $filePath): array
    {
        // Read all rows and return the first one (headers)
        $rows = $this->read($filePath);
        // Use null coalescing operator to return empty array if no rows exist
        return $rows[0] ?? [];
    }
}
