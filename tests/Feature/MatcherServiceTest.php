<?php

use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\User;
use App\Services\Reconciliation\MatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupContext() {
    $user = User::factory()->create();
    $team = \App\Models\Team::forceCreate(['user_id' => $user->id, 'name' => 'Test Team', 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $archivo = \App\Models\Archivo::forceCreate([
       'user_id' => $user->id,
       'team_id' => $team->id,
       'path' => 'dummy.xml',
       'mime' => 'application/xml', // Corrected from mime_type
       'size' => 123,
       'checksum' => 'dummyhash',
       'estatus' => 'processed',
    ]);

    $banco = \App\Models\Banco::forceCreate(['nombre' => 'Test Bank', 'codigo' => 'B001']);

    return [$user, $team, $archivo, $banco];
}

test('it finds exact matches in the same month and year', function () {
    [$user, $team, $archivo, $banco] = setupContext();
    
    // Invoice: Jan 2026, $100
    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-MATCH-1',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-15',
        'rfc' => 'TEST010101AAA',
        'nombre' => 'Test Client',
    ]);

    // Movement: Jan 2026, $100
    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-16', // Diff 1 day
        'monto' => 100.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment 1',
        'hash' => 'hash1',
    ]);

    // 2. Execute Matcher
    $service = new MatcherService();
    // Tolerance 0.50, Month 01, Year 2026
    $matches = $service->findMatches($team->id, 0.50, 1, 2026);

    // 3. Verify
    expect($matches)->toHaveCount(1);
    expect($matches[0]['invoice']->id)->toBe($factura->id);
    expect($matches[0]['movement']->id)->toBe($movimiento->id);
    expect($matches[0]['difference'])->toBe(0.0);
});

test('it respects month and year boundaries', function () {
    [$user, $team, $archivo, $banco] = setupContext();

    // Invoice: Jan 2026
    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-DIFF-MONTH',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-31', 
        'rfc' => 'TEST010101BBB',
        'nombre' => 'Test Client',
    ]);

    // Movement: Feb 2026
    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-02-01', 
        'monto' => 100.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment Feb',
        'hash' => 'hash2',
    ]);

    $service = new MatcherService();
    
    // Search in Jan 2026
    $matchesJan = $service->findMatches($team->id, 0.50, 1, 2026);
    expect($matchesJan)->toBeEmpty();

    // Search in Feb 2026
    $matchesFeb = $service->findMatches($team->id, 0.50, 2, 2026);
    expect($matchesFeb)->toBeEmpty();
});

test('it allows matches within tolerance amount', function () {
    [$user, $team, $archivo, $banco] = setupContext();

    // Invoice: $100.00
    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-TOLERANCE',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-10',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    // Movement: $99.60 (Diff 0.40) -> Inside 0.50 tolerance
    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-10',
        'monto' => 99.60,
        'tipo' => 'abono',
        'descripcion' => 'Payment',
        'hash' => 'dummyhash',
    ]);

    $service = new MatcherService();
    $matches = $service->findMatches($team->id, 0.50, 1, 2026);

    expect($matches)->toHaveCount(1);
    expect(round($matches[0]['difference'], 2))->toBe(0.40);
});

test('it rejects matches outside tolerance amount', function () {
    [$user, $team, $archivo, $banco] = setupContext();

    // Invoice: $100.00
    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-OUTSIDE',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-10',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    // Movement: $99.40 (Diff 0.60) -> Outside 0.50 tolerance
    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-10',
        'monto' => 99.40,
        'tipo' => 'abono',
        'descripcion' => 'Payment',
        'hash' => 'dummyhash',
    ]);

    $service = new MatcherService();
    $matches = $service->findMatches($team->id, 0.50, 1, 2026);

    expect($matches)->toBeEmpty();
});

test('it filters out already reconciled items', function () {
    [$user, $team, $archivo, $banco] = setupContext();

    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-RECONCILED',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-10',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-10',
        'monto' => 100.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment',
        'hash' => 'dummyhash',
    ]);
    
    // Manually create a Conciliacion record
    \App\Models\Conciliacion::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'factura_id' => $factura->id,
        'movimiento_id' => $movimiento->id,
        'monto_aplicado' => 100.00,
        'tipo' => 'manual',
        'estatus' => 'conciliado',
    ]);

    $service = new MatcherService();
    $matches = $service->findMatches($team->id, 0.50, 1, 2026);

    expect($matches)->toBeEmpty();
});
