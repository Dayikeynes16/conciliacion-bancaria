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
        $rows = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection
        {
            public function collection(Collection $rows)
            {
                return $rows;
            }
        }, $filePath)->first();

        return $this->normalize($rows)->values()->toArray();
    }
}
