<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface BankParserInterface
{
    /**
     * Parse the bank statement file.
     *
     * @param  string  $filePath  Absolute path to the file.
     * @return Collection Collection of normalized movement data.
     */
    public function parse(string $filePath): Collection;
}
