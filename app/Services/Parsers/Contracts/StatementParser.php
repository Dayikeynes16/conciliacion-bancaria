<?php

namespace App\Services\Parsers\Contracts;

interface StatementParser
{
    /**
     * Parse the given file path and return an array of standardized movements.
     * 
     * @param string $filePath
     * @return array  [ ['fecha' => 'Y-m-d', 'monto' => 123.45, 'tipo' => 'cargo|abono', 'referencia' => '...', 'descripcion' => '...'] ]
     */
    public function parse(string $filePath): array;
}
