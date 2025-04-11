<?php

namespace Lightpack\Utils;

class Csv 
{
    private string $delimiter = ',';
    private string $enclosure = '"';
    private string $escape = '\\';
    private array $casts = [];
    private array $mappings = [];
    private array $excludes = [];

    /**
     * Read CSV with generators for memory efficiency.
     * 
     * @param string $file CSV file path
     * @param bool $hasHeader Use first row as header
     * @return \Generator Row by row data
     * @throws \RuntimeException If file cannot be opened
     */
    public function read(string $file, bool $hasHeader = true): \Generator 
    {
        if (!is_readable($file)) {
            throw new \RuntimeException("Cannot read file: {$file}");
        }

        $handle = fopen($file, 'r');
        $headers = $hasHeader ? fgetcsv($handle, 0, $this->delimiter) : null;

        while ($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) {
            if ($headers) {
                $row = array_combine($headers, $row);
                $row = $this->applyExcludes($row);
                $row = $this->applyMappings($row);
            }
            yield $this->castTypes($row);
        }

        fclose($handle);
    }

    /**
     * Write data to CSV, accepting any iterable.
     * 
     * @param string $file Output file path
     * @param iterable $data Data to write
     * @param array $headers Optional column headers
     * @return bool Success
     * @throws \RuntimeException If file cannot be opened for writing
     */
    public function write(string $file, iterable $data, array $headers = []): bool 
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("Cannot write to directory: {$dir}");
        }

        $handle = fopen($file, 'w');
        
        if ($headers) {
            fputcsv($handle, $headers, $this->delimiter, $this->enclosure, $this->escape);
        }

        foreach ($data as $row) {
            fputcsv($handle, $row, $this->delimiter, $this->enclosure, $this->escape);
        }

        return fclose($handle);
    }

    /**
     * Map column names or transform values.
     * 
     * @param array $mappings Column mappings ['old' => 'new'] or ['column' => callable]
     * @return self
     */
    public function map(array $mappings): self 
    {
        $this->mappings = $mappings;
        return $this;
    }

    /**
     * Exclude specified columns.
     * 
     * @param array $columns Columns to exclude
     * @return self
     */
    public function except(array $columns): self 
    {
        $this->excludes = $columns;
        return $this;
    }

    /**
     * Set column type casting.
     * 
     * @param array $types Column type hints (int, float, bool, date)
     * @return self
     */
    public function casts(array $types): self 
    {
        $this->casts = $types;
        return $this;
    }

    /**
     * Set CSV delimiter.
     * 
     * @param string $delimiter Delimiter character
     * @return self
     */
    public function setDelimiter(string $delimiter): self 
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Cast row values to specified types.
     * 
     * @param array $row Row data
     * @return array Processed row
     */
    protected function castTypes(array $row): array 
    {
        foreach ($this->casts as $column => $type) {
            if (!isset($row[$column])) {
                continue;
            }

            $row[$column] = match($type) {
                'int' => (int) $row[$column],
                'float' => (float) $row[$column],
                'bool' => filter_var($row[$column], FILTER_VALIDATE_BOOLEAN),
                'date' => strtotime($row[$column]),
                default => $row[$column]
            };
        }
        return $row;
    }

    private function applyMappings(array $row): array 
    {
        $result = [];
        foreach ($row as $key => $value) {
            if (isset($this->mappings[$key])) {
                $mapping = $this->mappings[$key];
                if (is_callable($mapping)) {
                    $result[$key] = $mapping($value);
                } else {
                    $result[$mapping] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function applyExcludes(array $row): array 
    {
        return array_diff_key($row, array_flip($this->excludes));
    }
}
