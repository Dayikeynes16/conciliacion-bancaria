<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\StatementParser;
use Exception;

class StatementParserFactory
{
    /**
     * Get the appropriate parser for the given bank code.
     *
     * @throws Exception
     */
    public static function make(string $identifier): StatementParser
    {


        // Check for Dynamic Format by ID (assuming identifier is the ID)
        if (is_numeric($identifier)) {
            $format = \App\Models\BankFormat::find($identifier);
            if ($format) {
                return new DynamicStatementParser($format);
            }
        }

        throw new Exception("No parser found for identifier: {$identifier}");
    }
}
