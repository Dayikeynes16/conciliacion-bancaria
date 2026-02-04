<?php

namespace App\Services\Parsers;

use App\Models\BankFormat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DynamicStatementParser extends AbstractBankParser
{
    protected BankFormat $format;

    public function __construct(BankFormat $format)
    {
        $this->format = $format;
    }

    protected function normalize(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            throw new \Exception('El archivo está vacío.');
        }

        // Convert column letters to 0-based indices
        $colMap = [
            'fecha' => $this->colToIndex($this->format->date_column),
            'descripcion' => $this->colToIndex($this->format->description_column),
            'monto' => $this->format->amount_column ? $this->colToIndex($this->format->amount_column) : -1,
            'debit' => $this->format->debit_column ? $this->colToIndex($this->format->debit_column) : -1,
            'credit' => $this->format->credit_column ? $this->colToIndex($this->format->credit_column) : -1,
            'referencia' => $this->format->reference_column ? $this->colToIndex($this->format->reference_column) : -1,
            'tipo' => $this->format->type_column ? $this->colToIndex($this->format->type_column) : -1,
        ];

        // Slice rows based on start_row. start_row is 1-based.
        // If start_row is 1, we take all. If 2, we skip 1.
        $startOffset = max(0, $this->format->start_row - 1);
        $dataRows = $rows->slice($startOffset);

        $parsed = $dataRows->map(function ($row, $key) use ($colMap) {
            try {
                // Get values using mapped indices
                $fechaVal = $row[$colMap['fecha']] ?? null;
                $descVal = $row[$colMap['descripcion']] ?? '';

                $montoVal = ($colMap['monto'] !== -1) ? ($row[$colMap['monto']] ?? 0) : 0;
                $debitVal = ($colMap['debit'] !== -1) ? ($row[$colMap['debit']] ?? 0) : 0;
                $creditVal = ($colMap['credit'] !== -1) ? ($row[$colMap['credit']] ?? 0) : 0;

                $refVal = ($colMap['referencia'] !== -1) ? ($row[$colMap['referencia']] ?? 'N/A') : 'N/A';
                $tipoVal = ($colMap['tipo'] !== -1) ? ($row[$colMap['tipo']] ?? '') : '';

                if (! $fechaVal) {
                    return null;
                }

                $fecha = $this->parseDate($fechaVal);

                // Strict check: if date parsing fails, return null (row ignored)
                if (! $fecha) {
                    return null;
                }

                $descripcion = (string) $descVal;
                $referencia = (string) $refVal;

                $rawMonto = 0;
                $monto = 0;
                $tipo = 'cargo'; // Default

                // Logic 1: Single Amount Column
                if ($colMap['monto'] !== -1) {
                    $rawMonto = $this->parseAmount($montoVal);
                    $monto = abs($rawMonto);

                    if ($monto == 0) {
                        return null;
                    }

                    // Type Column
                    if ($this->format->type_column) {
                        $tipoStr = strtolower((string) $tipoVal);
                        if (str_contains($tipoStr, 'abono') || str_contains($tipoStr, 'depósito') || str_contains($tipoStr, 'deposito') || str_contains($tipoStr, 'crédito')) {
                            $tipo = 'abono';
                        }
                    } else {
                        // Sign based
                        if ($rawMonto < 0) {
                            $tipo = 'cargo';
                        } else {
                            $tipo = 'abono';
                        }
                    }
                }
                // Logic 2: Separate Debit/Credit Columns
                else {
                    $debit = abs($this->parseAmount($debitVal));
                    $credit = abs($this->parseAmount($creditVal));

                    if ($debit == 0 && $credit == 0) {
                        return null;
                    }

                    // Priority: If both have values, logic is tricky. Usually mutually exclusive.
                    // If Debit > 0, it's a cargo.
                    if ($debit > 0) {
                        $monto = $debit;
                        $tipo = 'cargo';
                    } elseif ($credit > 0) {
                        $monto = $credit;
                        $tipo = 'abono';
                    }
                }

                return [
                    'fecha' => $fecha,
                    'descripcion' => $descripcion,
                    'referencia' => $referencia,
                    'monto' => $monto,
                    'tipo' => $tipo,
                ];

            } catch (\Exception $e) {
                return null;
            }
        });

        // Strict Validation: The parsed collection must contain a valid entry for the start_row.
        // Slice preserves keys. The key for the start row is $startOffset.
        // We check if $parsed has a non-null value at this offset.
        if (! isset($parsed[$startOffset]) || $parsed[$startOffset] === null) {
            throw new \Exception('Formato incorrecto: La Fila Inicial ('.($startOffset + 1).') no contiene datos válidos (Fecha o Monto). Verifique la configuración del formato.');
        }

        return $parsed->filter();
    }

    private function colToIndex($col)
    {
        $col = strtoupper($col);
        $length = strlen($col);
        $index = 0;
        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($col[$i]) - ord('A') + 1;
        }

        return $index - 1;
    }

    private function parseDate($value)
    {
        if (! $value) {
            return null;
        }
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAmount($value)
    {
        if (! $value) {
            return 0.0;
        }
        if (is_string($value)) {
            $value = str_replace(['$', ',', ' '], '', $value);
        }

        return (float) $value;
    }
}
