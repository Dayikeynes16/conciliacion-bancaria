<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MovimientoController extends Controller
{
    public function index()
    {
        $files = Archivo::whereHas('movimientos')
            ->with(['banco'])
            ->withCount('movimientos')
            ->latest()
            ->get();

        return Inertia::render('Movements/Index', [
            'files' => $files
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
