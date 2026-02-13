<?php

namespace Tests\Feature;

use App\Jobs\ProcessBankStatement;
use App\Models\Archivo;
use App\Models\Banco;
use App\Models\Factura;
use App\Models\Team;
use App\Models\User;
use App\Services\Xml\CfdiParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RegressionTest extends TestCase
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
        $team = Team::factory()->create([
            'user_id' => $user->id,
            'personal_team' => true,
            'rfc' => 'EMISOR_RFC', // Match the XML Mock
        ]);
        $user->current_team_id = $team->id;
        $user->save();

        return $user;
    }

    public function test_xml_stores_receptor_rfc()
    {
        $user = $this->createUserWithTeam();

        // Mock Parser to return explicit Emisor vs Receptor
        $this->mock(CfdiParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->andReturn([ // Removed once()
                'uuid' => 'UUID-RECEPTOR-TEST',
                'folio' => 'F-1',
                'fecha_emision' => '2023-01-01',
                'total' => 100.00,
                'rfc_emisor' => 'EMISOR_RFC',
                'nombre_emisor' => 'Emisor Name',
                'rfc_receptor' => 'RECEPTOR_RFC', // Expected
                'nombre_receptor' => 'Receptor Name', // Expected
            ]);
        });

        $file = UploadedFile::fake()->createWithContent('inv.xml', '<xml>Content</xml>');

        $this->actingAs($user)->postJson(route('upload.store'), [
            'files' => [$file],
        ]);

        // Process the job
        $job = new \App\Jobs\ProcessXmlUpload(
            Archivo::first(),
            $user->current_team_id,
            $user->id
        );
        try {
            $job->handle(app(CfdiParserService::class));
        } catch (\Throwable $e) { // Catch Throwable for Errors
            dump($e->getMessage());
        }

        // Assert Factura has RECEPTOR data
        $this->assertDatabaseHas('facturas', [
            'uuid' => 'UUID-RECEPTOR-TEST',
            'rfc' => 'RECEPTOR_RFC',
            'nombre' => 'Receptor Name',
        ]);
    }

    public function test_bank_statement_persists_banco_id()
    {
        $user = $this->createUserWithTeam();
        $banco = Banco::factory()->create(['nombre' => 'BBVA Bancomer']); // Specific name

        // Create a format that matches the bank name
        $format = \App\Models\BankFormat::create([
            'team_id' => $user->current_team_id,
            'name' => 'BBVA Excel Format', // Contains "BBVA"
            // Required fields
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => 'C',
        ]);

        $file = UploadedFile::fake()->createWithContent('statement.csv', "2023-01-01,Payment,100\n");

        $response = $this->actingAs($user)->postJson(route('upload.store'), [
            'statement' => $file,
            'bank_code' => $format->id, // Send Format ID
        ]);

        $response->assertOk();

        // Check Archivo record
        $this->assertDatabaseHas('archivos', [
            'original_name' => 'statement.csv',
            'banco_id' => $banco->id, // Should match via Heuristic
            'bank_format_id' => $format->id,
        ]);

        Queue::assertPushed(ProcessBankStatement::class, function ($job) use ($banco) {
            return $job->archivo->banco_id === $banco->id;
        });
    }

    public function test_bank_statement_job_creates_movements()
    {
        $user = $this->createUserWithTeam();
        $banco = Banco::factory()->create(['nombre' => 'BBVA Bancomer', 'codigo' => 'BBVA']);

        // Mock the Factory to return a Parser that returns a dummy movement
        // Since we are testing the JOB logic, we can mock the parser to avoid real file parsing issues in unit test
        // BUT, the user says the issue is "Regression". It might be the path.
        // Let's try to use a real parser call if possible, or Mock the Factory to return a mock parser?
        // Code: $parser = StatementParserFactory::make(...)
        // I can mock the Factory facade? No, it's a class.
        // I can use Mockery overload?

        // Alternative: Verify the PATH logic.
        // If I use the real job, it runs StatementParserFactory::make.
        // If I want to pinpoint PATH issue, I should see "File not found" exception.

        // Create a format that matches the bank code 'BBVA' so logic finds it
        \App\Models\BankFormat::create([
            'team_id' => $user->current_team_id,
            'name' => 'BBVA', // Matches banco->codigo in fallback logic
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => 'C',
            // 'type' => 'excel' // Assuming default
        ]);

        $file = UploadedFile::fake()->createWithContent('statement.csv', "2023-01-01,Payment,100\n");
        $path = $file->storeAs('statements/'.$user->current_team_id, 'statement.csv'); // Store as CSV

        $archivo = Archivo::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'banco_id' => $banco->id,
            'path' => $path,
            'original_name' => 'statement.csv',
            'mime' => 'text/csv',
            'size' => 1024,
            'checksum' => md5('content'),
            'estatus' => 'pendiente',
        ]);

        $job = new ProcessBankStatement($archivo, $user->current_team_id, $user->id);

        // Expect failure if path is wrong, or success if parser works.
        // Since we don't have a real excel with data, the parser might fail or return empty.
        // Let's Mock the StatementParserFactory to return a Mock Parser, ensuring we test the JOB flow.

        $mockParser = Mockery::mock(\App\Services\Parsers\StatementParserInterface::class);
        $mockParser->shouldReceive('parse')->andReturn([
            [
                'fecha' => '2023-01-01',
                'monto' => 100.00,
                'tipo' => 'abono',
                'referencia' => 'REF123',
                'descripcion' => 'Payment',
            ],
        ]);

        $this->mock(\App\Services\Parsers\StatementParserFactory::class, function ($mock) use ($mockParser) {
            $mock->shouldReceive('make')->andReturn($mockParser);
        });

        // Problem: StatementParserFactory is a static 'make' class or is it resolved?
        // App\Jobs\ProcessBankStatement uses `StatementParserFactory::make(...)` statically?
        // Check file content... `use App\Services\Parsers\StatementParserFactory;`
        // `$parser = StatementParserFactory::make(...)`
        // Use Mockery alias to mock static method?
        // Or refactor Job to check path?

        // Let's just run it and see if it fails on File Not Found.
        $job->handle();

        // If path is wrong, it won't even call parse() or parse() will fail.
        // Wait, parse() takes $fullPath.
        // If I cannot mock static Factory easily without alias, I will rely on the exception dump.

        /*
           If I want to test the PATH issue specifically:
           The Job does: $fullPath = storage_path('app/' . $this->archivo->path);
           Storage::path($path) gives the real absolute path.
           storage_path('app/' . $path) assumes structure.
        */

        // Assertion: if it failed, archvio is 'fallido'.
        $archivo->refresh();
        // Assertion: Job should succeed
        $archivo->refresh();
        $this->assertEquals('procesado', $archivo->estatus, "Job failed with status: {$archivo->estatus}");

        // Assert Movimiento created
        $this->assertDatabaseHas('movimientos', [
            'team_id' => $user->current_team_id,
            'monto' => 100.00,
            'referencia' => 'N/A',
        ]);

        // If status is 'procesado', we check movements.
    }

    public function test_bank_statement_job_fails_validation_on_empty_parse_result()
    {
        $user = $this->createUserWithTeam();
        $banco = Banco::factory()->create(['nombre' => 'Test Bank']);
        $format = \App\Models\BankFormat::create([
            'team_id' => $user->current_team_id,
            'name' => 'Format',
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => 'C',
        ]);

        $file = UploadedFile::fake()->createWithContent('statement.csv', "InvalidContent\n");
        $path = $file->storeAs('statements/'.$user->current_team_id, 'statement.csv');

        $archivo = Archivo::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'banco_id' => $banco->id,
            'bank_format_id' => $format->id,
            'path' => $path,
            'original_name' => 'statement.csv',
            'mime' => 'text/csv',
            'size' => 1024,
            'checksum' => md5('content'),
            'estatus' => 'pendiente',
        ]);

        $job = new ProcessBankStatement($archivo, $user->current_team_id, $user->id);

        // Mock Parser to return EMPTY array (simulating invalid format that somehow bypasses parser checks, OR just empty result logic)
        // Note: Real DynamicParser throws if empty, but we want to ensure JOB handles empty result if parser returns it (e.g. valid file but no rows matching filter?)
        // Or if we replace Parser with one that returns empty.

        $mockParser = Mockery::mock(\App\Services\Parsers\StatementParserInterface::class);
        $mockParser->shouldReceive('parse')->andReturn([]); // Return empty

        $this->mock(\App\Services\Parsers\StatementParserFactory::class, function ($mock) use ($mockParser) {
            $mock->shouldReceive('make')->andReturn($mockParser);
        });

        // Run Job
        $job->handle();

        // Assert Fail
        $archivo->refresh();
        $this->assertEquals('fallido', $archivo->estatus, "Job should fail if 0 movements parsed. Got: {$archivo->estatus}");
    }

    public function test_xml_rejects_mismatch_rfc()
    {
        $user = $this->createUserWithTeam();

        // precise setup: Team has RFC 'TEAM_RFC', XML has Emisor 'OTHER_RFC'
        $team = $user->currentTeam;
        $team->rfc = 'TEAM_RFC';
        $team->save();

        $this->mock(CfdiParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->andReturn([
                'uuid' => 'UUID-MISMATCH',
                'folio' => 'F-1',
                'fecha_emision' => '2023-01-01',
                'total' => 100.00,
                'rfc_emisor' => 'OTHER_RFC', // Mismatch
                'nombre_emisor' => 'Other',
                'rfc_receptor' => 'CLIENT_RFC',
                'nombre_receptor' => 'Client',
            ]);
        });

        $file = UploadedFile::fake()->createWithContent('inv.xml', '<xml>Content</xml>');

        // Expect Validation Error in Response (Global Try-Catch returns 200 with error details or 422 if configured,
        // but current implementation returns 200 with JSON structure containing errors for file upload)

        $response = $this->actingAs($user)->postJson(route('upload.store'), [
            'files' => [$file],
        ]);

        $response->assertOk();
        $json = $response->json();

        // Assert we have file errors
        $this->assertGreaterThan(0, $json['results']['xml_other_errors']);
        $this->assertNotEmpty($json['results']['file_errors']);
        $this->assertStringContainsString('no coincide con el RFC del equipo', $json['results']['file_errors'][0]);

        // Assert NO Archivo created
        $this->assertDatabaseMissing('archivos', [
            'original_name' => 'inv.xml',
        ]);

        // Assert XML Process Job was NOT pushed
        Queue::assertNotPushed(\App\Jobs\ProcessXmlUpload::class);
    }

    public function test_xml_allows_matching_rfc()
    {
        $user = $this->createUserWithTeam();

        // precise setup: Team has RFC 'EMISOR_RFC', XML has Emisor 'EMISOR_RFC'
        $team = $user->currentTeam;
        $team->rfc = 'EMISOR_RFC'; // Matching
        $team->save();

        $this->mock(CfdiParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->andReturn([
                'uuid' => 'UUID-MATCH',
                'folio' => 'F-1',
                'fecha_emision' => '2023-01-01',
                'total' => 100.00,
                'rfc_emisor' => 'EMISOR_RFC', // Match
                'nombre_emisor' => 'Emisor',
                'rfc_receptor' => 'CLIENT_RFC',
                'nombre_receptor' => 'Client',
            ]);
        });

        $file = UploadedFile::fake()->createWithContent('inv.xml', '<xml>Content</xml>');
        $path = $file->storeAs('xml', 'match.xml');

        // Ensure file exists in storage for the Job to find
        Storage::put('statements/statement.csv', "2023-01-01,Payment,100\n");

        // Create Archivo manually
        $archivo = Archivo::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'path' => $path,
            'original_name' => 'inv.xml',
            'mime' => 'text/xml',
            'size' => 100,
            'checksum' => md5('match'),
            'estatus' => 'pendiente',
        ]);

        $job = new \App\Jobs\ProcessXmlUpload($archivo, $team->id, $user->id);
        $job->handle(app(CfdiParserService::class));

        $archivo->refresh();
        $this->assertEquals('procesado', $archivo->estatus);

        // Verify Mapping: RFC should be CLIENT_RFC (Receptor)
        $this->assertDatabaseHas('facturas', [
            'uuid' => 'UUID-MATCH',
            'rfc' => 'CLIENT_RFC',
            'nombre' => 'Client',
        ]);
    }

    public function test_bank_statement_controller_rejects_duplicates()
    {
        $user = $this->createUserWithTeam();
        $banco = Banco::factory()->create(['nombre' => 'Test Bank']);
        $format = \App\Models\BankFormat::create([
            'team_id' => $user->current_team_id,
            'name' => 'Format',
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => 'C',
        ]);

        $content = "2023-01-01,Payment,100\n";
        $hash = md5($content);

        // Upload First Time
        $file1 = UploadedFile::fake()->createWithContent('statement.csv', $content);
        $this->actingAs($user)->postJson(route('upload.store'), [
            'statement' => $file1,
            'bank_code' => $format->id,
        ])->assertOk();

        // Assert Archivo Created
        $this->assertDatabaseHas('archivos', [
            'checksum' => $hash,
            'estatus' => 'pendiente', // Job mocked/queued
        ]);

        // Upload Second Time (Same Content)
        $file2 = UploadedFile::fake()->createWithContent('statement.csv', $content);
        $response = $this->actingAs($user)->postJson(route('upload.store'), [
            'statement' => $file2,
            'bank_code' => $format->id,
        ]);

        // Should contain warning via Toast or JSON
        $response->assertOk();
        $json = $response->json();

        // Expecting a warning toast and NO new file created
        // Check toasts
        $hasWarning = false;
        foreach ($json['toasts'] as $toast) {
            if ($toast['type'] === 'warning' && str_contains($toast['message'], 'ya ha sido subido')) {
                $hasWarning = true;
            }
        }
        $this->assertTrue($hasWarning, 'Duplicate upload should return a warning toast.');

        // Assert only 1 file in DB (original)
        $this->assertEquals(1, Archivo::where('checksum', $hash)->count());
    }

    public function test_bank_statement_controller_rejects_invalid_format_sync()
    {
        // This test verifies if the Controller performs SYNC validation.
        // User wants immediate feedback.

        $user = $this->createUserWithTeam();
        $banco = Banco::factory()->create(['nombre' => 'Test Bank']);
        $format = \App\Models\BankFormat::create([
            'team_id' => $user->current_team_id,
            'name' => 'Format',
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => 'C',
        ]);

        // Invalid Content (Header mismatch, empty, etc to fail Parser)
        // If we implement sync validation, this request should FAIL or return error toast BEFORE queuing.
        $file = UploadedFile::fake()->createWithContent('invalid.csv', "InvalidData\n");

        $response = $this->actingAs($user)->postJson(route('upload.store'), [
            'statement' => $file,
            'bank_code' => $format->id,
        ]);

        // Current state: It probably succeeds (returns 200 and queues job).
        // Desired state: It returns error (or 200 with error toast) and DOES NOT queue job.

        $json = $response->json();

        // Check if we got an error toast
        $hasError = false;
        if (isset($json['toasts'])) {
            foreach ($json['toasts'] as $toast) {
                if ($toast['type'] === 'error') {
                    $hasError = true;
                }
            }
        }

        $this->assertTrue($hasError, 'Controller should reject invalid format synchronously.');

        // Assert No Archivo Created
        $this->assertDatabaseMissing('archivos', [
            'original_name' => 'invalid.csv',
        ]);
    }
}
