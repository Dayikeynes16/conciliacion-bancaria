<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\StatementParser;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

abstract class AbstractBankParser implements StatementParser
{
    /**
     * Normalize the data from the bank file.
     */
    abstract protected function normalize(Collection $rows): Collection;

    public function parse(string $filePath): array
    {
        // STABILITY: Limit file size to 10MB to prevent memory exhaustion
        $maxSize = 10 * 1024 * 1024;
        if (filesize($filePath) > $maxSize) {
            throw new \Exception('El archivo es demasiado grande (Máx 10MB).');
        }

        // Load the file using Maatwebsite Excel
        // We assume the first sheet contains the data

        $readerType = null;
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (empty($extension)) {
            // Fallback to CSV if no extension (common for temp uploaded files)
            $readerType = \Maatwebsite\Excel\Excel::CSV;
        }

        // Check delimiter if CSV
        $delimiter = ',';
        if ($readerType === \Maatwebsite\Excel\Excel::CSV) {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                $line = fgets($handle);
                if ($line && strpos($line, ';') !== false) {
                    $delimiter = ';';
                }
                fclose($handle);
            }
        }

        try {
            $rows = Excel::toCollection(new class($delimiter) implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithCustomCsvSettings
            {
                protected $delimiter;

                public function __construct($delimiter)
                {
                    $this->delimiter = $delimiter;
                }

                public function collection(Collection $rows)
                {
                    return $rows;
                }

                public function getCsvSettings(): array
                {
                    return [
                        'delimiter' => $this->delimiter,
                        'input_encoding' => 'UTF-8', // robustness
                    ];
                }
            }, $filePath, null, $readerType)->first();
        } catch (\Throwable $e) {
            // throw $e; // Re-throw to be caught by Controller
            throw new \Exception('Error al leer el archivo: '.$e->getMessage());
        }

        return $this->normalize($rows)->values()->toArray();
    }
}
