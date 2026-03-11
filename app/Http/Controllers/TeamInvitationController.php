<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    /**
     * Show the invitation page (GET - safe, no state change).
     */
    public function show(Request $request, $token)
    {
        $invitation = TeamInvitation::with('team.owner')->where('token', $token)->firstOrFail();

        // Store intended URL so after login/register the user is redirected to the join page
        session(['url.intended' => route('team-invitations.join', $token)]);

        return Inertia::render('Teams/InvitationLanding', [
            'invitation' => $invitation,
            'isAuthenticated' => Auth::check(),
        ]);
    }

    /**
     * Accept the invitation (POST - requires CSRF token).
     */
    public function accept(Request $request, $token)
    {
        $invitation = TeamInvitation::with('team')->where('token', $token)->firstOrFail();

        if (! Auth::check()) {
            // Not logged in — store intended URL and redirect to login
            session(['url.intended' => route('team-invitations.join', $token)]);

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is the owner
        if ($invitation->team->user_id === $user->id) {
            $invitation->delete();

            return redirect()->route('dashboard')->with('info', 'Ya eres el propietario de este equipo.');
        }

        // Link user to team if not already linked
        if (! $invitation->team->users()->where('user_id', $user->id)->exists()) {
            $invitation->team->users()->attach($user->id, ['role' => $invitation->role]);
            $user->switchTeam($invitation->team);
        } else {
            $user->switchTeam($invitation->team);
        }

        $teamName = $invitation->team->name;
        $invitation->delete();

        return redirect()->route('dashboard')->with('success', 'Te has unido al equipo '.$teamName);
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
