<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:13',
        ]);

        $user = Auth::user();

        $team = Team::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'rfc' => $request->rfc,
            'personal_team' => false,
        ]);

        // Attach user as owner
        $user->teams()->attach($team, ['role' => 'owner']);

        // Switch to new team
        $user->switchTeam($team);

        return redirect()->route('dashboard'); // Redirect to dashboard to see new context
    }

    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        if ($team->user_id !== Auth::id()) {
            abort(403, 'Solo el propietario del equipo puede editarlo.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:13',
        ]);

        $team->update([
            'name' => $request->name,
            'rfc' => $request->rfc,
        ]);

        return back()->with('success', 'Equipo actualizado correctamente.');
    }
}
