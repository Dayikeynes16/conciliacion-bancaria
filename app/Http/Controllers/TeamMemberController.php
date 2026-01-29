<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
            abort(404, 'No team context found.');
        }

        return Inertia::render('Teams/Show', [
            'team' => $team, // Team model includes user_id
            'members' => $team->users,
            'invitations' => $team->invitations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $team = $request->user()->currentTeam;
        $email = $request->email;

        // Enforce Owner Permission
        if ($team->user_id !== $request->user()->id) {
            return back()->with('error', 'Solo el propietario del equipo puede agregar miembros.');
        }

        // Check if user is already in team
        if ($team->users()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'El usuario ya pertenece al equipo.']);
        }

        // Check if pending invitation exists
        if ($team->invitations()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Ya existe una invitación pendiente para este correo.']);
        }

        // Create Invitation
        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => 'member',
            'token' => Str::random(32),
        ]);

        // Send Email
        Mail::to($email)->send(new \App\Mail\TeamInvitationMail($invitation));

        return back()->with('success', 'Invitación enviada por correo.');
    }

    public function destroy(Request $request, $teamId, $userId)
    {
        $team = $request->user()->currentTeam;

        if ($team->id != $teamId) {
            abort(403);
        }

        // Enforce Owner Permission, UNLESS removing self (leaving team)
        if ($team->user_id !== $request->user()->id && $userId != $request->user()->id) {
            return back()->with('error', 'Solo el propietario del equipo puede eliminar miembros.');
        }

        // Prevent Owner from leaving their own team
        if ($team->user_id == $userId) {
            return back()->with('error', 'El propietario no puede salir del equipo. Debe eliminar el equipo o transferir la propiedad.');
        }

        $userToRemove = User::find($userId);

        if (! $userToRemove) {
            abort(404);
        }

        // Prevent removing self if owner, or ensure at least one owner remains?
        // For now, simpler logic:
        $team->users()->detach($userId);

        // If the user's current team is the one they were removed from, switch them to another team.
        if ($userToRemove->fresh()->current_team_id == $team->id) {
            // Priority 1: Personal (Owned) Team
            $newTeam = $userToRemove->ownedTeams()->first();

            // Priority 2: Any other team they belong to
            if (! $newTeam) {
                $newTeam = $userToRemove->teams()->first();
            }

            // Priority 3: Create a Personal Team if none exists (Safety Net)
            if (! $newTeam) {
                $newTeam = \App\Models\Team::create([
                    'user_id' => $userToRemove->id,
                    'name' => explode(' ', $userToRemove->name, 2)[0]."'s Team",
                    'personal_team' => true,
                ]);
            }

            // Force update current team
            $userToRemove->forceFill([
                'current_team_id' => $newTeam->id,
            ])->save();
        }

        return back()->with('success', 'Miembro eliminado del equipo.');
    }
}
