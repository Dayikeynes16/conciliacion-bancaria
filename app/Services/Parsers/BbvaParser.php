<?php

namespace App\Services\Parsers;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BbvaParser extends AbstractBankParser
{
    protected function normalize(Collection $rows): Collection
    {
        // Remove header row(s). Usually first row is header.
        if ($rows->isEmpty()) {
            throw new \Exception("El archivo está vacío.");
        }

        // Search for the header row in the first 20 rows
        $headerRowIndex = null;
        $header = null;

        foreach ($rows->take(20) as $index => $row) {
             $firstCol = trim($row[0] ?? '');
             
             // Check for standard BBVA headers
             if (str_contains(strtolower($firstCol), 'fecha') || str_contains(strtolower($firstCol), 'date')) {
                 $headerRowIndex = $index;
                 $header = $row;
                 break;
             }
        }

        if ($headerRowIndex === null) {
             // Fallback: Show the first row content in error for debugging
             $preview = $rows->first() ? implode(', ', $rows->first()->toArray()) : 'N/A';
             throw new \Exception("Formato inválido. No se encontró la fila de encabezados 'Fecha' en las primeras 20 filas. Fila 1 detectada: " . $preview);
        }

        // Data starts after the header row
        $dataRows = $rows->slice($headerRowIndex + 1); 

        return $dataRows->map(function ($row) {
            // Column mapping based on user screenshot:
            // 0: Fecha
            // 1: Descripción
            // 2: Abono (Deposit)
            // 3: Cargo (Withdrawal)
            // 4: Saldo
            
            try {
                $fecha = $this->parseDate($row[0]);
                $descripcion = $row[1] ?? '';
                
                // Parse amounts, potentially negative in source (e.g. -78.08 for cargo)
                $rawAbono = $this->parseAmount($row[2]);
                $rawCargo = $this->parseAmount($row[3]);
                
                $monto = 0;
                $tipo = 'cargo';

                // Logic: If Abono is non-zero, it's an Abono. If Cargo is non-zero, it's a Cargo.
                // We use abs() because sometimes exports show negative numbers in specific columns.
                
                if (abs($rawAbono) > 0) {
                    $monto = abs($rawAbono);
                    $tipo = 'abono';
                } elseif (abs($rawCargo) > 0) {
                    $monto = abs($rawCargo);
                    $tipo = 'cargo';
                }

                if ($monto == 0) {
                    return null; 
                }

                return [
                    'fecha' => $fecha,
                    'descripcion' => $descripcion,
                    'referencia' => 'N/A', 
                    'monto' => $monto,
                    'tipo' => $tipo,
                ];

            } catch (\Exception $e) {
                Log::warning("Error parsing row: " . json_encode($row) . " Error: " . $e->getMessage());
                return null;
            }
        })->filter(); // Remove nulls
    }

    private function parseDate($value)
    {
        if (!$value) return now();
        // Handle Excel numeric dates serialization if needed, or string dates
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        return Carbon::parse($value)->format('Y-m-d');
    }

    private function parseAmount($value)
    {
        if (!$value) return 0.0;
        if (is_string($value)) {
            // Remove currency symbols, commas
            $value = str_replace(['$', ',', ' '], '', $value);
        }
        return (float) $value;
    }
}
