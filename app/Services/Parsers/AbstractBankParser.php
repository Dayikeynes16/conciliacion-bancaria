<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\StatementParser;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

abstract class AbstractBankParser implements StatementParser
{
    /**
     * Normalize the data from the bank file.
     *
     * @param Collection $rows
     * @return Collection
     */
    abstract protected function normalize(Collection $rows): Collection;

    public function parse(string $filePath): array
    {
        // Load the file using Maatwebsite Excel
        // We assume the first sheet contains the data
        $rows = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
            public function collection(Collection $rows) {
                return $rows;
            }
        }, $filePath)->first();

        return $this->normalize($rows)->values()->toArray();
    }
}
