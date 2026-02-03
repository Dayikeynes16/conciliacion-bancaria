<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    public function accept(Request $request, $token)
    {
        $invitation = TeamInvitation::with('team.owner')->where('token', $token)->firstOrFail();

        // If user is logged in
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user is the owner
            if ($invitation->team->user_id === $user->id) {
                // Delete the invitation as it is redundant
                $invitation->delete();
                
                return redirect()->route('dashboard')->with('info', 'Ya eres el propietario de este equipo.');
            }

            // Link user to team if not already linked
            if (! $invitation->team->users()->where('user_id', $user->id)->exists()) {
                $invitation->team->users()->attach($user->id, ['role' => $invitation->role]);
                $invitation->delete();

                // Switch to that team
                $user->switchTeam($invitation->team);

                return redirect()->route('dashboard')->with('success', 'Te has unido al equipo '.$invitation->team->name);
            } else {
                $user->switchTeam($invitation->team);
                $invitation->delete(); // Clean up invite if already member? Or just redirect.

                return redirect()->route('dashboard')->with('info', 'Ya eres miembro de este equipo.');
            }
        }

        // If user is NOT logged in, show Invitation Landing Page
        // Store intended URL so after login they are redirected back here to "accept" it (but currently 'accept' triggers join logic immediately)
        // Actually, if we redirect back to 'accept' after login, it will hit the "Auth::check()" block above. Perfect.
        session(['url.intended' => route('team-invitations.accept', $token)]);

        return Inertia::render('Teams/InvitationLanding', [
            'invitation' => $invitation,
        ]);
    }

    public function destroy(Request $request, TeamInvitation $invitation)
    {
        if (! Auth::check()) {
            abort(403);
        }

        if ($invitation->team_id !== Auth::user()->current_team_id) {
            abort(403);
        }

        if ($invitation->team->user_id !== Auth::id()) {
            abort(403, 'Solo el propietario del equipo puede eliminar invitaciones.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitación eliminada.');
    }
}
