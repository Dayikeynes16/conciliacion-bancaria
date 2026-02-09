<?php

use App\Models\BankFormat;
use App\Models\User;
use App\Models\Team;

test('users can delete their own bank formats', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $format = BankFormat::create([
        'team_id' => $team->id,
        'name' => 'My Format',
        'start_row' => 1,
        'date_column' => 'A',
        'description_column' => 'B',
        'amount_column' => 'C',
    ]);

    $this->actingAs($user)
        ->delete(route('bank-formats.destroy', $format))
        ->assertRedirect();

    $this->assertDatabaseMissing('bank_formats', ['id' => $format->id]);
});

test('users cannot delete other teams bank formats', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'personal_team' => true]);
    $user->current_team_id = $team->id;
    $user->save();

    $otherUser = User::factory()->create();
    $otherTeam = Team::factory()->create(['user_id' => $otherUser->id, 'personal_team' => true]);
    $otherUser->current_team_id = $otherTeam->id;
    $otherUser->save();
    
    $otherFormat = BankFormat::create([
        'team_id' => $otherTeam->id,
        'name' => 'Other Format',
        'start_row' => 1,
        'date_column' => 'A',
        'description_column' => 'B',
        'amount_column' => 'C',
    ]);

    $this->actingAs($user)
        ->delete(route('bank-formats.destroy', $otherFormat))
        ->assertNotFound();

    $this->assertDatabaseHas('bank_formats', ['id' => $otherFormat->id]);
});
