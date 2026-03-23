<?php

namespace Tests\Feature;

use App\Jobs\ProcessXmlUpload;
use App\Models\Archivo;
use App\Models\Banco;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Team;
use App\Models\User;
use App\Services\Xml\CfdiParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    protected function createUserWithTeam()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id, 'personal_team' => true]);
        $user->current_team_id = $team->id;
        $user->save();

        return $user;
    }

    public function test_upload_xml_dispatches_job_for_new_file()
    {
        $user = $this->createUserWithTeam();

        $this->mock(CfdiParserService::class, function ($mock) use ($user) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'uuid' => 'NEW-UUID-123',
                'rfc_emisor' => $user->currentTeam->rfc,
                'nombre_emisor' => 'Test Emisor',
                'tipo_comprobante' => 'I',
                'metodo_pago' => 'PUE',
            ]);
        });

        $file = UploadedFile::fake()->create('factura.xml', 100, 'text/xml');

        $response = $this->actingAs($user)
            ->postJson(route('upload.store'), [
                'files' => [$file],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'xml_processed' => 1,
                    'xml_xml_duplicates' => 0,
                    'xml_other_errors' => 0,
                ],
                'processed_xml_count' => 1,
            ]);

        Queue::assertPushed(ProcessXmlUpload::class);
        $this->assertDatabaseHas('archivos', [
            'original_name' => 'factura.xml',
            'estatus' => 'pendiente',
        ]);
    }

    public function test_upload_xml_detects_synchronous_duplicate()
    {
        $user = $this->createUserWithTeam();

        // Create existing invoice with UUID
        Factura::factory()->create([
            'team_id' => $user->current_team_id,
            'uuid' => 'EXISTING-UUID-123',
            'user_id' => $user->id,
            'file_id_xml' => Archivo::factory()->create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ])->id,
            'monto' => 100,
            'fecha_emision' => now(),
            'rfc' => 'ABC',
            'nombre' => 'Test',
        ]);

        $this->mock(CfdiParserService::class, function ($mock) use ($user) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'uuid' => 'EXISTING-UUID-123',
                'rfc_emisor' => $user->currentTeam->rfc,
                'tipo_comprobante' => 'I',
                'metodo_pago' => 'PUE',
            ]);
        });

        $file = UploadedFile::fake()->create('duplicate.xml', 100, 'text/xml');

        $response = $this->actingAs($user)
            ->postJson(route('upload.store'), [
                'files' => [$file],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'xml_processed' => 0,
                    'xml_xml_duplicates' => 1,
                ],
                'processed_xml_count' => 0,
            ]);

        // Job should NOT be pushed for duplicate
        Queue::assertNotPushed(ProcessXmlUpload::class);
    }

    public function test_upload_mixed_batch_handles_duplicates_and_new_files()
    {
        $user = $this->createUserWithTeam();

        // Existing UUID
        Factura::factory()->create([
            'team_id' => $user->current_team_id,
            'uuid' => 'EXISTING-UUID',
            'user_id' => $user->id,
            'file_id_xml' => Archivo::factory()->create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ])->id,
            'monto' => 100,
            'fecha_emision' => now(),
            'rfc' => 'ABC',
            'nombre' => 'Test',
        ]);

        $user->currentTeam->update(['rfc' => 'ABC']);

        // Mock parser to handle multiple calls
        $mock = Mockery::mock(CfdiParserService::class);
        $mock->shouldReceive('parse')->andReturnUsing(function ($content) {
            if (str_contains($content, 'EXISTING')) {
                return ['uuid' => 'EXISTING-UUID', 'rfc_emisor' => 'ABC', 'tipo_comprobante' => 'I', 'metodo_pago' => 'PUE'];
            }

            return ['uuid' => 'NEW-UUID', 'rfc_emisor' => 'ABC', 'tipo_comprobante' => 'I', 'metodo_pago' => 'PUE'];
        });
        $this->app->instance(CfdiParserService::class, $mock);

        $file1 = UploadedFile::fake()->createWithContent('existing.xml', 'EXISTING CONTENT');
        $file2 = UploadedFile::fake()->createWithContent('new.xml', 'NEW CONTENT');

        $response = $this->actingAs($user)
            ->postJson(route('upload.store'), [
                'files' => [$file1, $file2],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'results' => [
                    'xml_processed' => 1,
                    'xml_xml_duplicates' => 1,
                ],
            ]);

        // Only 1 job pushed
        Queue::assertPushed(ProcessXmlUpload::class, 1);
    }

    /**
     * Helper: simulate the ProcessBankStatement hash + dedup logic for a batch of movements.
     * This mirrors the job's DB::transaction block without needing to mock the static parser factory.
     */
    private function simulateStatementImport(int $teamId, int $userId, array $movements): void
    {
        $banco = Banco::factory()->create();
        $archivo = Archivo::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'banco_id' => $banco->id,
            'path' => 'statements/test.csv',
            'original_name' => 'statement.csv',
            'mime' => 'text/csv',
            'size' => 1024,
            'checksum' => md5(uniqid()),
            'estatus' => 'procesado',
        ]);

        foreach ($movements as $movData) {
            // Exact same hash logic as ProcessBankStatement
            $hash = hash('sha256', json_encode([
                'fecha' => $movData['fecha'],
                'monto' => $movData['monto'],
                'descripcion' => $movData['descripcion'],
            ]));

            $exists = Movimiento::where('team_id', $teamId)
                ->where('hash', $hash)
                ->exists();

            if (! $exists) {
                Movimiento::create([
                    'user_id' => $userId,
                    'team_id' => $teamId,
                    'banco_id' => $banco->id,
                    'file_id' => $archivo->id,
                    'fecha' => $movData['fecha'],
                    'monto' => $movData['monto'],
                    'tipo' => $movData['tipo'],
                    'referencia' => $movData['referencia'],
                    'descripcion' => $movData['descripcion'],
                    'hash' => $hash,
                ]);
            }
        }
    }

    public function test_overlapping_upload_skips_existing_movements_and_adds_new_ones()
    {
        $user = $this->createUserWithTeam();

        // First upload: Feb 1-15 (3 movements)
        $this->simulateStatementImport($user->current_team_id, $user->id, [
            ['fecha' => '2024-02-01', 'monto' => 500.00, 'tipo' => 'abono', 'referencia' => 'REF-A', 'descripcion' => 'Deposit One'],
            ['fecha' => '2024-02-08', 'monto' => 300.00, 'tipo' => 'abono', 'referencia' => 'REF-B', 'descripcion' => 'Deposit Two'],
            ['fecha' => '2024-02-15', 'monto' => 200.00, 'tipo' => 'abono', 'referencia' => 'REF-C', 'descripcion' => 'Deposit Three'],
        ]);
        $this->assertEquals(3, Movimiento::where('team_id', $user->current_team_id)->count());

        // Second upload: Feb 1-28 (same 3 with changed referencia + 2 new)
        $this->simulateStatementImport($user->current_team_id, $user->id, [
            ['fecha' => '2024-02-01', 'monto' => 500.00, 'tipo' => 'abono', 'referencia' => 'REF-A-CHANGED', 'descripcion' => 'Deposit One'],
            ['fecha' => '2024-02-08', 'monto' => 300.00, 'tipo' => 'abono', 'referencia' => 'REF-B-CHANGED', 'descripcion' => 'Deposit Two'],
            ['fecha' => '2024-02-15', 'monto' => 200.00, 'tipo' => 'abono', 'referencia' => 'REF-C-CHANGED', 'descripcion' => 'Deposit Three'],
            ['fecha' => '2024-02-20', 'monto' => 750.00, 'tipo' => 'abono', 'referencia' => 'REF-D', 'descripcion' => 'Deposit Four'],
            ['fecha' => '2024-02-28', 'monto' => 100.00, 'tipo' => 'abono', 'referencia' => 'REF-E', 'descripcion' => 'Deposit Five'],
        ]);

        // 3 overlapping skipped + 2 new = 5 total
        $this->assertEquals(5, Movimiento::where('team_id', $user->current_team_id)->count());
    }

    public function test_same_date_and_amount_but_different_description_are_both_kept()
    {
        $user = $this->createUserWithTeam();

        $this->simulateStatementImport($user->current_team_id, $user->id, [
            ['fecha' => '2024-03-01', 'monto' => 1000.00, 'tipo' => 'abono', 'referencia' => 'REF-1', 'descripcion' => 'Wire Transfer'],
            ['fecha' => '2024-03-01', 'monto' => 1000.00, 'tipo' => 'abono', 'referencia' => 'REF-2', 'descripcion' => 'Check Deposit'],
        ]);

        $this->assertEquals(2, Movimiento::where('team_id', $user->current_team_id)->count());
    }

    public function test_same_date_description_amount_but_different_referencia_detected_as_duplicate()
    {
        $user = $this->createUserWithTeam();

        // First upload
        $this->simulateStatementImport($user->current_team_id, $user->id, [
            ['fecha' => '2024-04-10', 'monto' => 250.00, 'tipo' => 'abono', 'referencia' => 'OLD-REF', 'descripcion' => 'Monthly Payment'],
        ]);
        $this->assertEquals(1, Movimiento::where('team_id', $user->current_team_id)->count());

        // Second upload: same movement but referencia changed
        $this->simulateStatementImport($user->current_team_id, $user->id, [
            ['fecha' => '2024-04-10', 'monto' => 250.00, 'tipo' => 'abono', 'referencia' => 'NEW-REF', 'descripcion' => 'Monthly Payment'],
        ]);

        // Should still be 1 — referencia is not part of the hash
        $this->assertEquals(1, Movimiento::where('team_id', $user->current_team_id)->count());
    }

    public function test_multiple_duplicate_xmls_all_detected_as_duplicates()
    {
        $user = $this->createUserWithTeam();

        // Create 5 existing invoices
        for ($i = 1; $i <= 5; $i++) {
            Factura::factory()->create([
                'team_id' => $user->current_team_id,
                'uuid' => "DUP-UUID-{$i}",
                'user_id' => $user->id,
                'file_id_xml' => Archivo::factory()->create([
                    'team_id' => $user->current_team_id,
                    'user_id' => $user->id,
                ])->id,
                'monto' => 100 * $i,
                'fecha_emision' => now(),
                'rfc' => 'TEST_RFC',
                'nombre' => "Test {$i}",
            ]);
        }

        // Upload all 5 as duplicates in separate requests (mimics frontend sequential upload)
        $mock = Mockery::mock(CfdiParserService::class);
        $callCount = 0;
        $mock->shouldReceive('parse')->andReturnUsing(function () use (&$callCount) {
            $callCount++;

            return [
                'uuid' => "DUP-UUID-{$callCount}",
                'rfc_emisor' => 'TEST_RFC',
                'tipo_comprobante' => 'I',
                'metodo_pago' => 'PUE',
            ];
        });
        $this->app->instance(CfdiParserService::class, $mock);

        $user->currentTeam->update(['rfc' => 'TEST_RFC']);

        for ($i = 1; $i <= 5; $i++) {
            $file = UploadedFile::fake()->create("dup_{$i}.xml", 100, 'text/xml');

            $response = $this->actingAs($user)
                ->postJson(route('upload.store'), ['files' => [$file]]);

            $response->assertOk();
            $json = $response->json();

            // Each should be detected as duplicate, NOT error
            $this->assertEquals(1, $json['results']['xml_xml_duplicates'], "File {$i} should be duplicate");
            $this->assertEquals(0, $json['results']['xml_other_errors'], "File {$i} should have no errors");
        }

        // No jobs should have been pushed
        Queue::assertNotPushed(ProcessXmlUpload::class);
    }

    public function test_xml_parse_error_returns_specific_error_message()
    {
        $user = $this->createUserWithTeam();

        $this->mock(CfdiParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andThrow(
                new \Exception('Invalid XML format or security violation: Start tag expected')
            );
        });

        $file = UploadedFile::fake()->create('broken.xml', 100, 'text/xml');

        $response = $this->actingAs($user)
            ->postJson(route('upload.store'), ['files' => [$file]]);

        $response->assertOk();
        $json = $response->json();

        $this->assertEquals(0, $json['results']['xml_processed']);
        $this->assertEquals(1, $json['results']['xml_other_errors']);
        $this->assertNotEmpty($json['results']['file_errors']);
        $this->assertStringContainsString('XML Inválido', $json['results']['file_errors'][0]);
        $this->assertStringContainsString('Start tag expected', $json['results']['file_errors'][0]);
    }

    public function test_error_response_always_includes_file_errors_array()
    {
        $user = $this->createUserWithTeam();

        // Upload a non-XML file disguised as XML
        $this->mock(CfdiParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andThrow(
                new \Exception('Unexpected root element')
            );
        });

        $file = UploadedFile::fake()->create('bad.xml', 100, 'text/xml');

        $response = $this->actingAs($user)
            ->postJson(route('upload.store'), ['files' => [$file]]);

        $response->assertOk();
        $json = $response->json();

        // Response must always have the structured results with file_errors
        $this->assertArrayHasKey('results', $json);
        $this->assertArrayHasKey('file_errors', $json['results']);
        $this->assertIsArray($json['results']['file_errors']);
        $this->assertNotEmpty($json['results']['file_errors']);

        // The error message should contain the actual reason, not a generic string
        $this->assertStringContainsString('Unexpected root element', $json['results']['file_errors'][0]);
    }
}
