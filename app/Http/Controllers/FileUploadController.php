<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Banco;
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
            'xml_errors' => 0,
            'statement_processed' => 0,
            'statement_skipped' => 0, // Add skipped count
            'statement_error' => null,
        ];

        DB::beginTransaction();

        try {
            // Process XML Files (Facturas)
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Store File
                    $teamId = auth()->user()->current_team_id;
                    $path = $file->storeAs(
                        'uploads/teams/' . $teamId . '/xml',
                        Str::uuid() . '_' . $file->getClientOriginalName()
                    );

                    // Parse Content
                    $content = Storage::get($path);
                    $data = $cfdiParser->parse($content);

                    // Create Archivo Record
                    $archivo = Archivo::create([
                        'team_id' => $teamId,
                        'path' => $path,
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'estatus' => 'procesado',
                    ]);

                    // Check duplicate UUID
                    $exists = Factura::where('uuid', $data['uuid'])->exists(); 
                    
                    if ($exists) {
                         // Decide if we throw or just skip. User usually wants to know.
                         // For now, let's skip/throw as before but maybe safer to skip?
                         // Existing logic threw exception. Let's keep strict for invoices for now or just skip to avoid breaking batch?
                         // User said "preventing duplicated movements", didn't specify invoices, but safer to skip and count error.
                         // But current code throws. Let's keep it consistent or improve.
                         // Actually, sticking to original logic for invoices for now to minimize scope creep unless needed.
                         throw new \Exception("La factura con UUID {$data['uuid']} ya fue registrada previamente.");
                    }
                    
                    Factura::create([
                        'file_id_xml' => $archivo->id,
                        'uuid' => $data['uuid'],
                        'monto' => $data['total'],
                        'fecha_emision' => $data['fecha_emision'],
                        'rfc' => $data['rfc_emisor'], 
                        'nombre' => $data['nombre_emisor'],
                        'verificado' => true,
                    ]);

                    $results['xml_processed']++;
                }
            }

            // Process Bank Statement (Movimientos)
            if ($request->hasFile('statement')) {
                $file = $request->file('statement');
                
                // Store File
                $teamId = auth()->user()->current_team_id;
                $path = $file->storeAs(
                    'uploads/teams/' . $teamId . '/banco',
                    Str::uuid() . '_' . $file->getClientOriginalName()
                );
                
                // Calculate checksum/hash of the file content
                $checksum = md5_file($file->getRealPath());

                // Check if this file was already uploaded by this team (using checksum)
                $existingFile = Archivo::where('checksum', $checksum)
                    ->where('size', $file->getSize())
                    ->whereHas('banco') // Ensure it's a bank, although context implies it
                    ->first();

                if ($existingFile) {
                    $results['statement_skipped']++;
                    // throw new \Exception("Este archivo de movimientos ya fue subido anteriormente (ID: {$existingFile->id}).");
                    // User requested generic notification. We can return error or just skip?
                    // "saying you cant upload this file because it was uploaded in the past"
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'statement' => "No se puede subir este archivo porque ya fue cargado anteriormente en este equipo.",
                    ]);
                }
                
                $bankCode = $request->input('bank_code', 'BBVA');
                // Identify Bank (Find by code or name)
                $banco = Banco::firstOrCreate(['codigo' => $bankCode], ['nombre' => $bankCode . ' Bank']); 

                // Create Archivo Record
                $archivo = Archivo::create([
                    'team_id' => $teamId,
                    'banco_id' => $banco->id,
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'checksum' => $checksum, // Ensure we save this!
                    'estatus' => 'procesado',
                ]);

                // Parse
                $fullPath = Storage::path($path);
                $parser = StatementParserFactory::make($banco->codigo);
                $movements = $parser->parse($fullPath);

                foreach ($movements as $mov) {
                    // Generate Hash: md5(fecha + monto + tipo + referencia + descripcion)
                    // Normalize float to 2 decimals to avoid precision issues in string
                    $normalizedMonto = number_format((float)$mov['monto'], 2, '.', '');
                    $hashString = $mov['fecha'] . $normalizedMonto . $mov['tipo'] . ($mov['referencia'] ?? '') . ($mov['descripcion'] ?? '');
                    $hash = md5($hashString);

                    // Check for duplicate in the CURRENT TEAM (scope applies to Movimiento)
                    $exists = Movimiento::where('hash', $hash)->exists();

                    if ($exists) {
                        $results['statement_skipped']++;
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
                    ]);
                    $results['statement_processed']++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Upload transaction failed: " . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Error al procesar archivos: ' . $e->getMessage()
            ]);
        }

        $message = "Proceso completado. XMLs: {$results['xml_processed']} (Fallidos: {$results['xml_errors']}). ";
        $message .= "Movimientos: {$results['statement_processed']} (Duplicados omitidos: {$results['statement_skipped']})";
        
        $type = 'success';

        if ($results['xml_processed'] == 0 && $results['statement_processed'] == 0) {
            if ($results['statement_skipped'] > 0) {
                 $message = "Todos los movimientos en el archivo ya existían y fueron omitidos.";
            } else {
                 $type = 'warning';
                 $message = "Se subieron los archivos pero no se detectaron registros válidos. Verifique el contenido o formato.";
            }
        }

        return back()->with($type, $message);
    }
}
