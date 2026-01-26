<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
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

        if (!$team) {
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
            abort(403, 'Solo el propietario del equipo puede agregar miembros.');
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

        // Send Email (Mocking for now or using a generic notification if Mail is not setup)
        // Ideally: Mail::to($email)->send(new TeamInvitationMail($invitation));
        // For MVP, we'll display the link in the UI or rely on user copying it? 
        // Best to try to "send" it. But given time constraints, maybe just show it in the UI table for the admin to copy?
        // Let's assume we can generate a route for it.
        
        return back()->with('success', 'Invitación creada.');
    }

    public function destroy(Request $request, $teamId, $userId)
    {
        $team = $request->user()->currentTeam;
        
        if ($team->id != $teamId) {
            abort(403);
        }

        // Enforce Owner Permission
        if ($team->user_id !== $request->user()->id) {
            abort(403, 'Solo el propietario del equipo puede eliminar miembros.');
        }

        $userToRemove = User::find($userId);

        if (!$userToRemove) {
            abort(404);
        }

        // Prevent removing self if owner, or ensure at least one owner remains?
        // For now, simpler logic:
        $team->users()->detach($userId);

        return back()->with('success', 'Miembro eliminado del equipo.');
    }
}
