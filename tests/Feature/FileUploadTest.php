<?php

use App\Models\User;
use App\Services\Parsers\BbvaParser;
use App\Services\Xml\CfdiParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('upload validation fails with invalid files', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('upload.store'), [
            'files' => ['not-an-xml.txt'],
            'statement' => 'not-an-excel.txt',
        ]);

    $response->assertSessionHasErrors(['files.0', 'statement']);
});

test('upload processes xml files correctly', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    // Mock Parser
    $this->mock(CfdiParserService::class, function ($mock) {
        $mock->shouldReceive('parse')
            ->andReturn([
                'uuid' => '12345-UUID',
                'total' => 1000.00,
                'fecha_emision' => '2023-01-01',
                'rfc_emisor' => 'ABC123456T12',
                'nombre_emisor' => 'Test Emisor',
                'rfc_receptor' => 'XYZ123456T12',
                'nombre_receptor' => 'Test Receptor',
            ]);
    });

    $file = UploadedFile::fake()->create('factura.xml', 100, 'text/xml');

    $response = $this->actingAs($user)
        ->post(route('upload.store'), [
            'files' => [$file],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('toasts');

    // Assert DB
    $this->assertDatabaseHas('archivos', [
        'user_id' => $user->id,
        'mime' => 'application/xml',
    ]);

    $this->assertDatabaseHas('facturas', [
        'user_id' => $user->id,
        'uuid' => '12345-UUID',
        'monto' => 1000.00,
    ]);
});

test('upload processes statement file correctly', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    // Mock Bbva Parser
    $this->mock(BbvaParser::class, function ($mock) {
        $mock->shouldReceive('parse')
            ->andReturn([
                [
                    'fecha' => '2023-01-01',
                    'descripcion' => 'Deposit',
                    'monto' => 500.00,
                    'tipo' => 'abono',
                    'referencia' => 'REF123',
                ],
            ]);
    });

    $file = UploadedFile::fake()->create('edo_cuenta.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $response = $this->actingAs($user)
        ->post(route('upload.store'), [
            'statement' => $file,
            'bank_code' => 'BBVA',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('toasts', function ($toasts) {
        return collect($toasts)->contains(function ($toast) {
            return str_contains($toast['message'], 'movimientos cargados') && $toast['type'] === 'success';
        });
    });

    $this->assertDatabaseHas('movimientos', [
        'user_id' => $user->id,
        'monto' => 500.00,
        'tipo' => 'abono',
    ]);
});

test('upload processes xml files correctly with json response', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->mock(CfdiParserService::class, function ($mock) {
        $mock->shouldReceive('parse')->andReturn([
            'uuid' => 'JSON-UUID',
            'total' => 200.00,
            'fecha_emision' => '2023-01-02',
            'rfc_emisor' => 'JSON123456T12',
            'nombre_emisor' => 'JSON Emisor',
            'rfc_receptor' => 'XYZ123456T12',
            'nombre_receptor' => 'Test Receptor',
        ]);
    });

    $file = UploadedFile::fake()->create('factura_json.xml', 100, 'text/xml');

    $response = $this->actingAs($user)
        ->postJson(route('upload.store'), [
            'files' => [$file],
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'processed_xml_count' => 1,
        ]);
});

test('can delete uploaded file', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $team = \App\Models\Team::create([
        'user_id' => $user->id,
        'name' => 'Test Team',
        'personal_team' => true,
    ]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    // Mock Parser
    $this->mock(CfdiParserService::class, function ($mock) {
        $mock->shouldReceive('parse')->andReturn([
            'uuid' => 'DELETE-UUID',
            'total' => 500.00,
            'fecha_emision' => '2023-01-05',
            'rfc_emisor' => 'DEL123456T12',
            'nombre_emisor' => 'Delete Emisor',
            'rfc_receptor' => 'XYZ123456T12',
            'nombre_receptor' => 'Test Receptor',
        ]);
    });
    
    $file = UploadedFile::fake()->create('factura.xml', 100, 'application/xml');

    // 1. Upload file
    $response = $this->actingAs($user)
        ->postJson(route('upload.store'), [
            'files' => [$file],
        ]);
    
    $response->assertOk();
    
    $archivo = \App\Models\Archivo::first();
    $this->assertNotNull($archivo);
    
    // 2. Delete file via FacturaController (invoices.destroy)
    $response = $this->actingAs($user)
        ->delete(route('invoices.destroy', $archivo->id));
        
    $response->assertRedirect(route('invoices.index'));
    $this->assertDatabaseMissing('archivos', ['id' => $archivo->id]);
    $this->assertDatabaseMissing('facturas', ['file_id_xml' => $archivo->id]);
});
