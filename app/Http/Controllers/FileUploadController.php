<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Banco;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Services\Parsers\StatementParserFactory;
use App\Services\Xml\CfdiParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function store(Request $request, CfdiParserService $cfdiParser)
    {
        $request->validate([
            'files.*' => 'nullable|file|mimes:xml',
            'statement' => 'nullable|file|mimes:xlsx,xls,csv',
            'bank_code' => 'required_with:statement|string',
        ]);

        $results = [
            'xml_processed' => 0,
            'xml_xml_duplicates' => 0,
            'xml_other_errors' => 0,
        ];

        $toasts = [];
        $xmlProcessed = 0;

        // Process XML Files (Facturas)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                try {
                    DB::transaction(function () use ($file, &$toasts, $request, $cfdiParser, &$xmlProcessed) {
                        $teamId = auth()->user()->current_team_id;
                        $path = $file->storeAs(
                            'uploads/teams/'.$teamId.'/xml',
                            Str::uuid().'_'.$file->getClientOriginalName()
                        );

                        // Parse Content
                        $content = Storage::get($path);
                        $data = $cfdiParser->parse($content);

                        // Validate RFC Emisor against Team RFC
                        $team = \App\Models\Team::find($teamId);
                        if ($team->rfc && $data['rfc_emisor'] !== $team->rfc) {
                             throw new \Exception("RFC mismatch: The invoice belongs to another team/enterprise ({$data['rfc_emisor']}). Expected: {$team->rfc}");
                        }

                        // Check duplicate UUID within the TEAM
                        $exists = Factura::where('team_id', $teamId)->where('uuid', $data['uuid'])->exists();

                        if ($exists) {
                           throw new \Exception("Duplicate entry");
                        }

                        // Create Archivo Record
                        $archivo = Archivo::create([
                            'team_id' => $teamId,
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                            'estatus' => 'procesado',
                        ]);

                        Factura::create([
                            'file_id_xml' => $archivo->id,
                            'uuid' => $data['uuid'],
                            'monto' => $data['total'],
                            'fecha_emision' => $data['fecha_emision'],
                            'rfc' => $data['rfc_receptor'],
                            'nombre' => $data['nombre_receptor'],
                            'verificado' => true,
                            'team_id' => $teamId,
                            'user_id' => auth()->id(),
                        ]);

                        $xmlProcessed++;
                    });
                } catch (\Throwable $e) {
                     // Check for Duplicate Entry SQL error specifically
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        $results['xml_xml_duplicates']++;
                    } elseif (str_contains($e->getMessage(), 'RFC mismatch')) {
                        $results['xml_other_errors']++; 
                        $results['file_errors'][] = "Error ({$file->getClientOriginalName()}): Esta factura no fue emitida por ti (RFC Incorrecto).";
                    } else {
                        Log::error("XML Error: " . $e->getMessage());
                        $results['xml_other_errors']++;
                        $results['file_errors'][] = "Error ({$file->getClientOriginalName()}): " . $e->getMessage();
                    }
                }
            }
        }
        
        if ($xmlProcessed > 0) {
             $toasts[] = ['type' => 'success', 'message' => "Se procesaron correctamente {$xmlProcessed} factura(s)."];
        }
        
        if ($results['xml_xml_duplicates'] > 0) {
            $toasts[] = ['type' => 'error', 'message' => "{$results['xml_xml_duplicates']} factura(s) ya existían y fueron omitidas."];
        }

        if ($results['xml_other_errors'] > 0) {
             $toasts[] = ['type' => 'error', 'message' => "{$results['xml_other_errors']} factura(s) fallaron por otros errores."];
        }

        // Process Bank Statement (Movimientos)
        if ($request->hasFile('statement')) {
            $file = $request->file('statement');
            try {
                 DB::transaction(function () use ($file, $request, &$toasts) {
                    // Store File
                    $teamId = auth()->user()->current_team_id;
                    $path = $file->storeAs(
                        'uploads/teams/'.$teamId.'/banco',
                        Str::uuid().'_'.$file->getClientOriginalName()
                    );

                    // Calculate checksum
                    $checksum = md5_file($file->getRealPath());

                    // Check if this file was already uploaded
                    $existingFile = Archivo::where('checksum', $checksum)
                        ->where('size', $file->getSize())
                        ->where('team_id', $teamId)
                        ->whereHas('banco')
                        ->first();

                    if ($existingFile) {
                         // We throw a specific code or message to catch it below
                         throw new \Exception("DUPLICATE_FILE");
                    }

                    $bankCode = $request->input('bank_code');
                    
                    $bankName = $bankCode . ' Bank';
                    if (is_numeric($bankCode)) {
                         $format = \App\Models\BankFormat::find($bankCode);
                         if ($format) {
                             $bankName = $format->name;
                         }
                    }

                    $banco = Banco::firstOrCreate(['codigo' => $bankCode], ['nombre' => $bankName]);

                    // Determine format ID if numeric
                    $bankFormatId = null;
                    if (is_numeric($bankCode)) {
                        $bankFormatId = (int) $bankCode;
                    }

                    // Create Archivo Record
                    $archivo = Archivo::create([
                        'team_id' => $teamId,
                        'banco_id' => $banco->id,
                        'bank_format_id' => $bankFormatId,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'checksum' => $checksum,
                        'estatus' => 'procesado',
                    ]);

                    // Parse
                    $fullPath = Storage::path($path);
                    $parser = StatementParserFactory::make($banco->codigo);
                    $movements = $parser->parse($fullPath);
                    
                    $processed = 0;
                    $skipped = 0;

                    foreach ($movements as $mov) {
                        $normalizedMonto = number_format((float) $mov['monto'], 2, '.', '');
                        $hashString = $mov['fecha'].$normalizedMonto.$mov['tipo'].($mov['referencia'] ?? '').($mov['descripcion'] ?? '');
                        $hash = md5($hashString);

                        // Check for duplicate
                        $exists = Movimiento::where('team_id', $teamId)->where('hash', $hash)->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        Movimiento::create([
                            'banco_id' => $banco->id,
                            'file_id' => $archivo->id,
                            'fecha' => $mov['fecha'],
                            'monto' => $mov['monto'],
                            'tipo' => $mov['tipo'],
                            'descripcion' => $mov['descripcion'],
                            'referencia' => $mov['referencia'],
                            'hash' => $hash,
                            'team_id' => $teamId,
                            'user_id' => auth()->id(),
                        ]);
                        $processed++;
                    }
                    
                    if ($processed > 0) {
                        $toasts[] = ['type' => 'success', 'message' => "Estado de Cuenta: {$processed} movimientos cargados."];
                    }
                    if ($skipped > 0) {
                        $toasts[] = ['type' => 'warning', 'message' => "Estado de Cuenta: {$skipped} movimientos duplicados fueron omitidos."];
                    }
                    
                    if ($processed == 0 && $skipped == 0) {
                         $toasts[] = ['type' => 'warning', 'message' => "El archivo se leyó pero no se encontraron movimientos. Verifique el formato."];
                    }
                });

            } catch (\Throwable $e) {
                if ($e->getMessage() === 'DUPLICATE_FILE') {
                     $toasts[] = ['type' => 'warning', 'message' => "Estado de Cuenta: El archivo ya había sido cargado previamente."];
                } else {
                     $toasts[] = ['type' => 'error', 'message' => "Error en Estado de Cuenta: " . $e->getMessage()];
                }
            }
        }

        if (empty($toasts)) {
            $toasts[] = ['type' => 'info', 'message' => "No se seleccionaron archivos para procesar."];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'results' => $results,
                'toasts' => $toasts,
                'processed_xml_count' => $xmlProcessed,
            ]);
        }

        return back()->with('toasts', $toasts);
    }
}
