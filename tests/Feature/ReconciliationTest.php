<?php

use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\User;
use App\Models\Team;
use App\Models\Conciliacion;
use App\Models\Archivo;
use App\Models\Banco;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('workbench page retrieves unreconciled items', function () {
    $user = User::factory()->create();
    $team = Team::forceCreate(['user_id' => $user->id, 'name' => 'Test Team', 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $archivo = Archivo::forceCreate([
       'user_id' => $user->id,
       'team_id' => $team->id,
       'path' => 'dummy.xml',
       'mime' => 'application/xml',
       'size' => 123,
       'checksum' => 'hash',
       'estatus' => 'processed',
    ]);

    // Create Invoice
    Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-1',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-15',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    $response = $this->actingAs($user)
        ->get(route('reconciliation.index', ['month' => 1, 'year' => 2026]));

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reconciliation/Workbench')
            ->has('invoices', 1)
            ->has('movements', 0)
        );
});

test('auto reconciliation endpoint returns matches', function () {
    $user = User::factory()->create();
    $team = Team::forceCreate(['user_id' => $user->id, 'name' => 'Test Team', 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $archivo = Archivo::forceCreate([
       'user_id' => $user->id,
       'team_id' => $team->id,
       'path' => 'dummy.xml',
       'mime' => 'application/xml',
       'size' => 123,
       'checksum' => 'hash',
       'estatus' => 'processed',
    ]);
    
    $banco = Banco::forceCreate(['nombre' => 'Bank', 'codigo' => 'B001']);

    // Match Pair
    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-MATCH',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-15',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-15',
        'monto' => 100.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment',
        'hash' => 'hash1',
    ]);

    // Use POST for auto
    $response = $this->actingAs($user)
        ->post(route('reconciliation.auto'), ['month' => 1, 'year' => 2026]);

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reconciliation/Matches')
            ->has('matches', 1)
            ->where('matches.0.invoice.uuid', 'UUID-MATCH')
        );
});

test('manual reconciliation stores record', function () {
    $user = User::factory()->create();
    $team = Team::forceCreate(['user_id' => $user->id, 'name' => 'Test Team', 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $archivo = Archivo::forceCreate([
       'user_id' => $user->id,
       'team_id' => $team->id,
       'path' => 'dummy.xml',
       'mime' => 'application/xml',
       'size' => 123,
       'checksum' => 'hash',
       'estatus' => 'processed',
    ]);
    
    $banco = Banco::forceCreate(['nombre' => 'Bank', 'codigo' => 'B001']);

    $factura = Factura::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-MANUAL',
        'monto' => 500.00,
        'fecha_emision' => '2026-01-10',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);

    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-12',
        'monto' => 500.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment Manual',
        'hash' => 'hash2',
    ]);

    $response = $this->actingAs($user)
        ->post(route('reconciliation.store'), [
            'invoice_ids' => [$factura->id],
            'movement_ids' => [$movimiento->id],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('conciliacions', [ // Corrected table name
        'factura_id' => $factura->id,
        'movimiento_id' => $movimiento->id,
        'monto_aplicado' => 500.00,
        'estatus' => 'conciliado',
    ]);
});

test('cannot reconcile items from another team', function () {
    $user = User::factory()->create();
    $team = Team::forceCreate(['user_id' => $user->id, 'name' => 'My Team', 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $otherUser = User::factory()->create();
    $otherTeam = Team::forceCreate(['user_id' => $otherUser->id, 'name' => 'Other Team', 'personal_team' => true]); // Added personal_team

    $archivo = Archivo::forceCreate([
       'user_id' => $otherUser->id,
       'team_id' => $otherTeam->id,
       'path' => 'dummy.xml',
       'mime' => 'application/xml',
       'size' => 123,
       'checksum' => 'hash',
       'estatus' => 'processed',
    ]);
    
    // Invoice belongs to OTHER team
    $factura = Factura::create([
        'user_id' => $otherUser->id,
        'team_id' => $otherTeam->id,
        'file_id_xml' => $archivo->id,
        'uuid' => 'UUID-OTHER',
        'monto' => 100.00,
        'fecha_emision' => '2026-01-15',
        'rfc' => 'TEST',
        'nombre' => 'Client',
    ]);
    
    $banco = Banco::forceCreate(['nombre' => 'Bank', 'codigo' => 'B001']);

    // Movement belongs to MY team (valid)
    $movimiento = Movimiento::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'banco_id' => $banco->id,
        'file_id' => $archivo->id,
        'fecha' => '2026-01-15',
        'monto' => 100.00,
        'tipo' => 'abono',
        'descripcion' => 'Payment',
        'hash' => 'hash3',
    ]);

    // Try to reconcile My Movement with Other Invoice
    // Expecting 500 or Exception because IDOR check -> 'Invalid or unauthorized records selected.'
    // Since MatcherService throws generic Exception, Laravel might render 500 in test.
    // However, without handling, it bubbles up.
    
    // Let's expect an exception.
    $this->withoutExceptionHandling();
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid or unauthorized records selected');

    $this->actingAs($user) 
        ->post(route('reconciliation.store'), [
            'invoice_ids' => [$factura->id],
            'movement_ids' => [$movimiento->id],
        ]);
});
