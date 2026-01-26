<?php

namespace App\Http\Controllers;

use App\Models\Tolerancia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ToleranciaController extends Controller
{
    /**
     * Show the form for editing the tolerance.
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        // Authorization: Check if user owns the current team
        if ($user->id !== $team->user_id) {
            abort(403, 'Solo el propietario del equipo puede configurar la tolerancia.');
        }

        // Get or create tolerance for this team
        // Since we are using TeamOwned model, it will automatically scope to current team
        // but for firstOrCreate we might need to be explicit or rely on the boot method
        $tolerancia = Tolerancia::firstOrCreate(
            ['team_id' => $team->id],
            [
                'monto' => 0.00,
                'user_id' => $user->id, // Assign current owner as creator
                'dias' => 0
            ]
        );

        return Inertia::render('Settings/Tolerance', [
            'tolerancia' => $tolerancia
        ]);
    }

    /**
     * Update the tolerance in storage.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if ($user->id !== $team->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'monto' => 'required|numeric|min:0',
            'dias' => 'required|integer|min:0',
        ]);

        $tolerancia = Tolerancia::firstOrCreate(['team_id' => $team->id]);
        
        $tolerancia->update([
            'monto' => $request->monto,
            'dias' => $request->dias,
        ]);

        return Redirect::route('settings.tolerance')->with('success', 'Configuración de tolerancia actualizada.');
    }
}
