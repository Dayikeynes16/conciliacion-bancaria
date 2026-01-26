<?php

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GenericImport; // Assuming this exists or we can use a closure
use Illuminate\Support\Collection;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$path = 'storage/app/private/uploads/teams/banco/d2289077-b41a-4d6d-b964-d53d4c399036_Copia de movimientos.xlsx';

if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit(1);
}

try {
    // Run the actual parser
    $parser = new \App\Services\Parsers\BbvaParser();
    try {
        $results = $parser->parse($path);
        echo "Parsing SUCCESS! Imported " . count($results) . " rows.\n";
        echo "First result: " . json_encode($results[0] ?? []) . "\n";
    } catch (\Exception $e) {
        echo "Parsing FAILED: " . $e->getMessage() . "\n";
    }


} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
