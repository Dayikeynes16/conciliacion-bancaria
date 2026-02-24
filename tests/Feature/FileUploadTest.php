<?php

namespace Tests\Feature;

use App\Jobs\ProcessXmlUpload;
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
                'rfc_emisor' => $user->currentTeam->rfc, // Valid Emisor
                'nombre_emisor' => 'Test Emisor', // Added for completeness
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
            'file_id_xml' => \App\Models\Archivo::factory()->create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id, // Added user_id
            ])->id,
            'monto' => 100,
            'fecha_emision' => now(),
            'rfc' => 'ABC',
            'nombre' => 'Test',
        ]);

        $this->mock(CfdiParserService::class, function ($mock) use ($user) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'uuid' => 'EXISTING-UUID-123',
                'rfc_emisor' => $user->currentTeam->rfc, // Valid Emisor
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
            'file_id_xml' => \App\Models\Archivo::factory()->create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id, // Added user_id
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
            // Need to match content to distinguish calls?
            // The fake file content is accessible via getRealPath if stored, but here we pass UploadedFile.
            // file_get_contents in controller reads the temp file.
            // UploadedFile::fake()->createWithContent() populates the temp file.

            if (str_contains($content, 'EXISTING')) {
                return ['uuid' => 'EXISTING-UUID', 'rfc_emisor' => 'ABC']; // Match Team RFC
            }

            return ['uuid' => 'NEW-UUID', 'rfc_emisor' => 'ABC']; // Match Team RFC
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
}
