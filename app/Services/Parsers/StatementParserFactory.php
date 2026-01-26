<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\StatementParser;
use App\Models\Banco;
use Exception;

class StatementParserFactory
{
    /**
     * Get the appropriate parser for the given bank code.
     *
     * @param string $bankCode
     * @return StatementParser
     * @throws Exception
     */
    public static function make(string $bankCode): StatementParser
    {
        return match (strtoupper($bankCode)) {
            'BBVA' => app(BbvaParser::class),
            // Add other banks here, e.g. 'BANAMEX' => app(BanamexParser::class),
            default => throw new Exception("No parser found for bank code: {$bankCode}"),
        };
    }
}
