<?php

use App\Models\Archivo;
use App\Models\ExportRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('marks stale export requests as failed', function () {
    $user = User::factory()->create();
    $stuckExport = ExportRequest::factory()->create([
        'user_id' => $user->id,
        'team_id' => $user->current_team_id,
        'status' => 'processing',
        'updated_at' => Carbon::now()->subHours(3),
    ]);

    $freshExport = ExportRequest::factory()->create([
        'user_id' => $user->id,
        'team_id' => $user->current_team_id,
        'status' => 'processing',
        'updated_at' => Carbon::now()->subMinutes(10),
    ]);

    artisan('queue:cleanup-stuck')
        ->expectsOutput("Marked ExportRequest #{$stuckExport->id} as failed.")
        ->assertExitCode(0);

    expect($stuckExport->fresh()->status)->toBe('failed');
    expect($stuckExport->fresh()->error_message)->toContain('abandoned');
    expect($freshExport->fresh()->status)->toBe('processing');
});

it('marks stale archivo uploads as fallido', function () {
    $user = User::factory()->create();

    $stuckArchivo = Archivo::forceCreate([
        'team_id' => $user->current_team_id,
        'user_id' => $user->id,
        'original_name' => 'test.xml',
        'path' => 'uploads/test.xml',
        'mime' => 'application/xml',
        'size' => 1024,
        'estatus' => 'procesando',
        'created_at' => Carbon::now()->subHours(3),
        'updated_at' => Carbon::now()->subHours(3),
    ]);

    $freshArchivo = Archivo::forceCreate([
        'team_id' => $user->current_team_id,
        'user_id' => $user->id,
        'original_name' => 'fresh.xml',
        'path' => 'uploads/fresh.xml',
        'mime' => 'application/xml',
        'size' => 1024,
        'estatus' => 'procesando',
        'created_at' => Carbon::now()->subMinutes(10),
        'updated_at' => Carbon::now()->subMinutes(10),
    ]);

    artisan('queue:cleanup-stuck')
        ->expectsOutput("Marked Archivo #{$stuckArchivo->id} as fallido.")
        ->assertExitCode(0);

    expect($stuckArchivo->fresh()->estatus)->toBe('fallido');
    expect($freshArchivo->fresh()->estatus)->toBe('procesando');
});
