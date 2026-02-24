<?php

use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows a user to download their own export from their own team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $export = ExportRequest::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'status' => 'completed',
    ]);

    actingAs($user)
        ->get(route('reconciliation.export.status', $export->id))
        ->assertSuccessful()
        ->assertJson(['status' => 'completed']);
});

it('denies a user from downloading an export from another team (404 for security)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $exportB = ExportRequest::factory()->create([
        'user_id' => $userB->id,
        'team_id' => $userB->current_team_id,
        'status' => 'completed',
    ]);

    actingAs($userA)
        ->get(route('reconciliation.export.status', $exportB->id))
        ->assertNotFound();
});

it('denies a user from downloading an export owned by another user in the SAME team (403 Forbidden)', function () {
    $userA = User::factory()->create();
    $team = $userA->currentTeam;

    $userB = User::factory()->create();
    $userB->forceFill(['current_team_id' => $team->id])->saveQuietly();

    $exportA = ExportRequest::factory()->create([
        'user_id' => $userA->id,
        'team_id' => $team->id,
        'status' => 'completed',
    ]);

    actingAs($userB)
        ->get(route('reconciliation.export.status', $exportA->id))
        ->assertForbidden();
});
