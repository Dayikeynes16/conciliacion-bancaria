<?php

namespace App\Services\Parsers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BbvaParser extends AbstractBankParser
{
    protected function normalize(Collection $rows): Collection
    {
        // Remove header row(s). Usually first row is header.
        if ($rows->isEmpty()) {
            throw new \Exception('El archivo está vacío.');
        }

        // Search for the header row in the first 20 rows
        $headerRowIndex = null;
        $header = null;
        $colMap = [
            'fecha' => -1,
            'descripcion' => -1,
            'abono' => -1,
            'cargo' => -1,
            'saldo' => -1,
        ];

        foreach ($rows->take(20) as $index => $row) {
            // Search for Fecha column
            $rowValues = array_map(function ($val) {
                return strtolower(trim((string) $val));
            }, $row->toArray());

            // Check if this row looks like a header
            if (in_array('fecha', $rowValues) || in_array('date', $rowValues) || in_array('día', $rowValues) || in_array('dia', $rowValues) || in_array('dìa', $rowValues) || in_array('dÌa', $rowValues)) {
                $headerRowIndex = $index;
                $header = $row;

                // Map columns
                foreach ($rowValues as $colIndex => $colName) {
                    // Normalize column name to handle encoding weirdness if needed
                    $colNameClean = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $colName));

                    if (str_contains($colName, 'fecha') || str_contains($colName, 'date') || str_contains($colName, 'dia') || str_contains($colName, 'dÌa')) {
                        $colMap['fecha'] = $colIndex;
                    } elseif (str_contains($colName, 'descripci') || str_contains($colName, 'concepto')) {
                        $colMap['descripcion'] = $colIndex;
                    } elseif (str_contains($colName, 'abono') || str_contains($colName, 'depósito') || str_contains($colName, 'deposito') || str_contains($colName, 'crédito') || str_contains($colName, 'credito')) {
                        $colMap['abono'] = $colIndex;
                    } elseif (str_contains($colName, 'cargo') || str_contains($colName, 'retiro')) {
                        $colMap['cargo'] = $colIndex;
                    } elseif (str_contains($colName, 'saldo')) {
                        $colMap['saldo'] = $colIndex;
                    }
                }
                break;
            }
        }

        if ($headerRowIndex === null) {
            // Fallback: Show the first row content in error for debugging
            $preview = $rows->first() ? implode(', ', $rows->first()->toArray()) : 'N/A';
            throw new \Exception("Formato inválido. No se encontró la fila de encabezados 'Fecha' o 'Día' en las primeras 20 filas. Fila 1 detectada: ".$preview);
        }

        // Validate required columns
        if ($colMap['fecha'] === -1) {
            throw new \Exception("No se encontró la columna 'Fecha' o 'Día'.");
        }

        // Data starts after the header row
        $dataRows = $rows->slice($headerRowIndex + 1);

        return $dataRows->map(function ($row) use ($colMap) {
            try {
                // Get values using mapped indices
                $fechaVal = $row[$colMap['fecha']] ?? null;
                $descVal = ($colMap['descripcion'] !== -1) ? ($row[$colMap['descripcion']] ?? '') : '';
                $abonoVal = ($colMap['abono'] !== -1) ? ($row[$colMap['abono']] ?? 0) : 0;
                $cargoVal = ($colMap['cargo'] !== -1) ? ($row[$colMap['cargo']] ?? 0) : 0;

                $fecha = $this->parseDate($fechaVal);
                $descripcion = $descVal;

                // Parse amounts
                $rawAbono = $this->parseAmount($abonoVal);
                $rawCargo = $this->parseAmount($cargoVal);

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
                Log::warning('Error parsing row: '.json_encode($row).' Error: '.$e->getMessage());

                return null;
            }
        })->filter(); // Remove nulls
    }

    private function parseDate($value)
    {
        if (! $value) {
            return now();
        }
        // Handle Excel numeric dates serialization if needed, or string dates
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function parseAmount($value)
    {
        if (! $value) {
            return 0.0;
        }
        if (is_string($value)) {
            // Remove currency symbols, commas
            $value = str_replace(['$', ',', ' '], '', $value);
        }

        return (float) $value;
    }
}
