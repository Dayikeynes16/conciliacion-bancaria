<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamInvitationController extends Controller
{
    public function accept(Request $request, $token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        // If user is logged in
        if (Auth::check()) {
            $user = Auth::user();

            // Link user to team
            $invitation->team->users()->attach($user->id, ['role' => $invitation->role]);
            
            // Switch to that team
            $user->switchTeam($invitation->team);

            $invitation->delete();

            return redirect()->route('dashboard')->with('success', 'Te has unido al equipo ' . $invitation->team->name);
        } 
        
        // If user is NOT logged in, we should redirect to register/login with the token intent.
        // For simplicity in MVP, we might require them to register first, or handle it via a specific "register with infinite" flow.
        // Let's redirect to register page with a token query param.
        
        return redirect()->route('register', ['team_invite' => $token]);
    }
}
