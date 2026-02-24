<?php

use App\Exports\Sheets\ConciliatedInvoicesSheet;
use App\Exports\Sheets\ConciliatedMovementsSheet;
use App\Models\Archivo;
use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('verifies that Facturas Conciliadas sheet uses correct amounts for N-M', function () {
    $user = User::factory()->create();
    $teamId = $user->current_team_id;
    $groupId = (string) Str::uuid();

    $file = Archivo::forceCreate([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'path' => 'test.xml',
        'original_name' => 'test.xml',
        'mime' => 'text/xml',
        'size' => 100,
        'estatus' => 'procesando',
    ]);

    // Scenario: 2 Invoices (100, 50) vs 1 Movement (150)
    $inv1 = Factura::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 100, 'file_id_xml' => $file->id]);
    $inv2 = Factura::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 50, 'file_id_xml' => $file->id]);
    $mov = Movimiento::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 150, 'file_id' => $file->id]);

    Conciliacion::create([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'group_id' => $groupId,
        'factura_id' => $inv1->id,
        'movimiento_id' => $mov->id,
        'monto_aplicado' => 100,
        'estatus' => 'conciliado',
        'fecha_conciliacion' => now(),
    ]);

    Conciliacion::create([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'group_id' => $groupId,
        'factura_id' => $inv2->id,
        'movimiento_id' => $mov->id,
        'monto_aplicado' => 50,
        'estatus' => 'conciliado',
        'fecha_conciliacion' => now(),
    ]);

    $sheet = new ConciliatedInvoicesSheet($teamId, null, null, null, null);
    $rows = $sheet->query()->get();

    expect($rows)->toHaveCount(2);

    foreach ($rows as $row) {
        $mapped = $sheet->map($row);
        expect($mapped[1])->toBe($groupId);
        expect((float) $mapped[8])->toEqual(0.0);

        if ($row->factura_id == $inv1->id) {
            expect((float) $mapped[6])->toEqual(100.0);
            expect((float) $mapped[7])->toEqual(100.0);
        } else {
            expect((float) $mapped[6])->toEqual(50.0);
            expect((float) $mapped[7])->toEqual(50.0);
        }
    }
});

it('verifies that Movimientos Conciliados sheet uses correct amounts for N-M', function () {
    $user = User::factory()->create();
    $teamId = $user->current_team_id;
    $groupId = (string) Str::uuid();

    $file = Archivo::forceCreate([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'path' => 'test.xml',
        'original_name' => 'test.xml',
        'mime' => 'text/xml',
        'size' => 100,
        'estatus' => 'procesando',
    ]);

    // Scenario: 1 Invoice (200) vs 2 Movements (50, 150)
    $inv = Factura::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 200, 'file_id_xml' => $file->id]);
    $mov1 = Movimiento::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 50, 'file_id' => $file->id]);
    $mov2 = Movimiento::factory()->create(['team_id' => $teamId, 'user_id' => $user->id, 'monto' => 150, 'file_id' => $file->id]);

    Conciliacion::create([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'group_id' => $groupId,
        'factura_id' => $inv->id,
        'movimiento_id' => $mov1->id,
        'monto_aplicado' => 50,
        'estatus' => 'conciliado',
        'fecha_conciliacion' => now(),
    ]);

    Conciliacion::create([
        'team_id' => $teamId,
        'user_id' => $user->id,
        'group_id' => $groupId,
        'factura_id' => $inv->id,
        'movimiento_id' => $mov2->id,
        'monto_aplicado' => 150,
        'estatus' => 'conciliado',
        'fecha_conciliacion' => now(),
    ]);

    $sheet = new ConciliatedMovementsSheet($teamId, null, null, null, null);
    $rows = $sheet->query()->get();

    expect($rows)->toHaveCount(2);

    foreach ($rows as $row) {
        $mapped = $sheet->map($row);
        expect($mapped[1])->toBe($groupId);
        expect((float) $mapped[8])->toEqual(0.0);

        if ($row->movimiento_id == $mov1->id) {
            expect((float) $mapped[5])->toEqual(50.0);
        } else {
            expect((float) $mapped[5])->toEqual(150.0);
        }

        expect((float) $mapped[6])->toEqual(200.0);
        expect((float) $mapped[7])->toEqual(200.0);
    }
});
