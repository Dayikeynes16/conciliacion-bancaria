<?php

namespace Tests\Feature;

use App\Jobs\GenerateReconciliationExcelExportJob;
use App\Jobs\GenerateReconciliationPdfExportJob;
use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReconciliationExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('local');
    }

    protected function createUserWithTeam()
    {
        $user = User::factory()->create();
        $team = \App\Models\Team::factory()->create(['user_id' => $user->id, 'personal_team' => true]);
        $user->current_team_id = $team->id;
        $user->save();

        return $user;
    }

    public function test_export_endpoint_creates_request_and_dispatches_job()
    {
        $user = $this->createUserWithTeam();

        $response = $this->actingAs($user)->getJson(route('reconciliation.export', ['format' => 'xlsx']));

        $response->assertOk()
            ->assertJsonStructure(['id', 'status', 'message']);

        $this->assertDatabaseHas('export_requests', [
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'type' => 'xlsx',
            'status' => 'queued',
        ]);

        Queue::assertPushed(GenerateReconciliationExcelExportJob::class);
    }

    public function test_pdf_export_dispatches_pdf_job()
    {
        $user = $this->createUserWithTeam();

        $this->actingAs($user)->getJson(route('reconciliation.export', ['format' => 'pdf']));

        Queue::assertPushed(GenerateReconciliationPdfExportJob::class);
    }

    public function test_check_status_returns_correct_status()
    {
        $user = $this->createUserWithTeam();
        $export = ExportRequest::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'type' => 'xlsx',
            'status' => 'processing',
        ]);

        $response = $this->actingAs($user)->getJson(route('reconciliation.export.status', $export->id));

        $response->assertOk()
            ->assertJson(['status' => 'processing']);
    }

    public function test_download_endpoint_returns_file_when_completed()
    {
        $user = $this->createUserWithTeam();
        $path = "exports/{$user->current_team_id}/{$user->id}/test.xlsx";
        Storage::put($path, 'dummy content');

        $export = ExportRequest::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'type' => 'xlsx',
            'status' => 'completed',
            'file_path' => $path,
            'file_name' => 'test.xlsx',
        ]);

        $response = $this->actingAs($user)->get(route('reconciliation.export.download', $export->id));

        $response->assertOk();
        $this->assertTrue($response->headers->contains('content-disposition', 'attachment; filename=test.xlsx'));
    }

    public function test_user_cannot_access_other_users_export()
    {
        $user1 = $this->createUserWithTeam();
        $user2 = $this->createUserWithTeam();

        $export = ExportRequest::create([
            'user_id' => $user1->id,
            'team_id' => $user1->current_team_id,
            'type' => 'xlsx',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user2)->getJson(route('reconciliation.export.status', $export->id));
        $response->assertForbidden();

        $response = $this->actingAs($user2)->get(route('reconciliation.export.download', $export->id));
        $response->assertForbidden();
    }
}
