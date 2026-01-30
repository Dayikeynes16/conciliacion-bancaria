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
        $date = $request->input('date');

        $files = Archivo::where('team_id', auth()->user()->current_team_id)
            ->whereNotNull('banco_id')
            ->where(function ($query) use ($month, $year, $date) {
                $query->whereHas('movimientos', function ($q) use ($month, $year, $date) {
                    if ($date) {
                        $q->whereDate('fecha', $date);
                    } elseif ($month && $year) {
                        $q->whereMonth('fecha', $month)
                          ->whereYear('fecha', $year);
                    }
                })->orWhereDoesntHave('movimientos');
            })
            // If filtering by date, we might also want to filter files uploaded on that date?
            // User likely means "Movements dated X" or "Files uploaded on X".
            // Since "Movimientos Bancarios" page lists Files, filtering by movement date is ambiguous (a file has many dates).
            // Usually "Filter by day" on a file list means "Date Uploaded".
            // But the code above filters files that HAVE movements in that date.
            // Let's stick to the existing pattern: Filter files containing movements in that range.
            // BUT, maybe the user wants to filter by `created_at` (Upload Date)?
            // "Archivos de Movimientos Cargados" -> "Fecha de Carga".
            // If I filter by "day", it probably means "Fecha de Carga".
            // The existing filter ($month/$year) uses `movimientos` date?
            // Line 20: `whereMonth('fecha', $month)`. 'fecha' is movement date.
            // Wait, if a file has movements from multiple months, does it show up?
            // If I upload a file today with movements from Jan, does it show up in Jan filter? Yes.
            // If the user said "filter by day", usually for FILES list it means when it was uploaded?
            // "Archivos de Movimientos Cargados... Fecha de Carga".
            // The table shows "Fecha de Carga".
            // I will implement DATE filter on `created_at` OR `movimientos.fecha`.
            // Given "Archivos...", filtering by Upload Date (`created_at`) makes more sense for "daily work".
            // However, consistent with Month/Year filter...
            // Let's support both or decide.
            // I will add a check: if `date` is provided, filter by `created_at` of the FILE?
            // Or `movimientos.fecha`?
            // Let's assume `created_at` for "Day" filter on "Files" page makes more sense to find what I just uploaded.
            // Actually, let's filter by `created_at` if `date` is present.
            ->when($date, function ($q) use ($date) {
                return $q->whereDate('created_at', $date);
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
                'date' => $date,
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

    public function batchDestroy(\Illuminate\Http\Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se han seleccionado archivos.');
        }

        $files = Archivo::where('team_id', auth()->user()->current_team_id)
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($files as $file) {
            if (\Illuminate\Support\Facades\Storage::exists($file->path)) {
                \Illuminate\Support\Facades\Storage::delete($file->path);
            }
            $file->delete();
            $count++;
        }

        return redirect()->route('movements.index')->with('success', "Se han eliminado {$count} archivos correctamente.");
    }
}
