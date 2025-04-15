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
    private ?int $limit = null;
    private ?int $maxAllowed = null;
    /** @var callable|null */
    private $validator = null;
    private string $onInvalid = 'skip';  // 'skip', 'collect', or 'fail'
    private array $errors = [];

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

        // Reset errors
        $this->errors = [];

        // If max rows check is needed, count total rows first
        if ($this->maxAllowed !== null) {
            $totalRows = 0;
            $countHandle = fopen($file, 'r');
            if ($hasHeader) {
                fgetcsv($countHandle, 0, $this->delimiter, $this->enclosure, $this->escape); // Skip header
            }
            while (fgetcsv($countHandle, 0, $this->delimiter, $this->enclosure, $this->escape)) {
                $totalRows++;
            }
            fclose($countHandle);

            if ($totalRows > $this->maxAllowed) {
                throw new \RuntimeException(
                    "CSV contains {$totalRows} rows. Maximum {$this->maxAllowed} rows allowed."
                );
            }
        }

        $handle = fopen($file, 'r');
        $headers = $hasHeader ? fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape) : null;
        $count = 0;
        $rowNum = 0;

        while ($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) {
            $rowNum++;
            
            if ($this->limit !== null && $count >= $this->limit) {
                break;
            }

            if ($headers) {
                $row = array_combine($headers, $row);
                $row = $this->applyExcludes($row);
                $row = $this->applyMappings($row);
                $row = $this->applyCasts($row);
            }

            // Validate if validator exists
            if ($this->validator) {
                try {
                    $result = ($this->validator)($row);
                    
                    // Convert result to array of errors
                    $errors = [];
                    if (is_string($result)) {
                        $errors = [$result];
                    } elseif (is_array($result)) {
                        $errors = $result;
                    } elseif ($result === false) {
                        $errors = ['Failed validation'];
                    }

                    if ($errors) {
                        $error = "Row {$rowNum}: " . implode(', ', $errors);
                        if ($this->onInvalid === 'fail') {
                            throw new \RuntimeException($error);
                        }
                        if ($this->onInvalid === 'collect') {
                            $this->errors[] = $error;
                        }
                        if ($this->onInvalid === 'skip') {
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    $error = "Row {$rowNum}: " . $e->getMessage();
                    if ($this->onInvalid === 'fail') {
                        throw new \RuntimeException($error);
                    }
                    if ($this->onInvalid === 'collect') {
                        $this->errors[] = $error;
                    }
                    if ($this->onInvalid === 'skip') {
                        continue;
                    }
                }
            }

            yield $row;
            $count++;
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

        // Write headers first
        if ($headers) {
            $headers = $this->transformHeaders($headers);
            fputcsv($handle, $headers, $this->delimiter, $this->enclosure, $this->escape);
        }

        // Transform each row
        foreach ($data as $row) {
            // Apply transformations
            $row = $this->applyCasts($row, true);
            $row = $this->applyMappings($row, true);
            $row = $this->applyExcludes($row);

            // Create a new row with ordered values
            if ($headers) {
                $orderedRow = [];
                foreach ($headers as $header) {
                    $orderedRow[] = $row[$header] ?? '';
                }
                $row = $orderedRow;
            }

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
     * Set maximum number of rows to process
     */
    public function limit(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Limit must be a positive number');
        }
        $this->limit = $count;
        return $this;
    }

    /**
     * Set maximum allowed rows (throws error if exceeded)
     */
    public function max(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Maximum rows must be a positive number');
        }
        $this->maxAllowed = $count;
        return $this;
    }

    /**
     * Add row validator. Return true if valid, false if invalid, or array of error messages
     */
    public function validate(callable $validator, string $onInvalid = 'skip'): self
    {
        if (!in_array($onInvalid, ['skip', 'collect', 'fail'])) {
            throw new \InvalidArgumentException(
                "Invalid onInvalid value. Must be 'skip', 'collect', or 'fail'"
            );
        }
        $this->validator = $validator;
        $this->onInvalid = $onInvalid;
        return $this;
    }

    /**
     * Get validation errors (only if onInvalid is 'collect')
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyCasts(array $row, bool $reverse = false): array 
    {
        foreach ($this->casts as $column => $type) {
            if (!isset($row[$column])) {
                continue;
            }

            if ($reverse) {
                // Convert back to string for CSV
                $row[$column] = match($type) {
                    'int', 'float' => (string) $row[$column],
                    'bool' => $row[$column] ? 'true' : 'false',
                    'date' => date('Y-m-d H:i:s', $row[$column]),
                    default => $row[$column]
                };
            } else {
                // Convert from string to type
                $row[$column] = match($type) {
                    'int' => (int) $row[$column],
                    'float' => (float) $row[$column],
                    'bool' => filter_var($row[$column], FILTER_VALIDATE_BOOLEAN),
                    'date' => strtotime($row[$column]),
                    default => $row[$column]
                };
            }
        }
        return $row;
    }

    private function applyMappings(array $row, bool $reverse = false): array 
    {
        $result = [];
        if ($reverse) {
            // Reverse the mappings for writing
            $reverseMappings = array_flip($this->mappings);
            foreach ($row as $key => $value) {
                $newKey = $reverseMappings[$key] ?? $key;
                if (is_callable($this->mappings[$newKey] ?? null)) {
                    // Skip callable transformations on write
                    $result[$newKey] = $value;
                } else {
                    $result[$newKey] = $value;
                }
            }
        } else {
            // Normal mappings for reading
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
        }
        return $result;
    }

    private function applyExcludes(array $row): array 
    {
        return array_diff_key($row, array_flip($this->excludes));
    }

    private function transformHeaders(array $headers): array 
    {
        $reverseMappings = array_flip($this->mappings);
        return array_map(function($header) use ($reverseMappings) {
            return $reverseMappings[$header] ?? $header;
        }, $headers);
    }
}
