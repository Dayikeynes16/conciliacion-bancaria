<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Inertia\Inertia;

class MovimientoController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $files = Archivo::where('team_id', auth()->user()->current_team_id)
            ->whereNotNull('banco_id')
            ->where(function ($query) use ($month, $year) {
                $query->whereHas('movimientos', function ($q) use ($month, $year) {
                    if ($month && $year) {
                        $q->whereMonth('fecha', $month)
                          ->whereYear('fecha', $year);
                    }
                })->orWhereDoesntHave('movimientos');
            })
            ->with(['banco'])
            ->withCount('movimientos')
            ->latest()
            ->get();

        return Inertia::render('Movements/Index', [
            'files' => $files,
            'filters' => [
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    public function show($fileId)
    {
        $movements = \App\Models\Movimiento::where('file_id', $fileId)
            ->withCount('conciliaciones')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($movements);
    }

    public function destroy(Archivo $file)
    {
        // Delete physical file
        if (\Illuminate\Support\Facades\Storage::exists($file->path)) {
            \Illuminate\Support\Facades\Storage::delete($file->path);
        }

        // Delete record (Cascades to Movimientos if set up)
        $file->delete();

        return redirect()->route('movements.index')->with('success', 'Archivo de movimientos eliminado correctamente.');
    }
}
