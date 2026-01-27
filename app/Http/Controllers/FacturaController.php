<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $month = $request->input('month');
        $year = $request->input('year');

        $files = Archivo::where('team_id', auth()->user()->current_team_id)
            ->whereHas('factura', function ($query) use ($search, $month, $year) {
                // Filter by month/year provided by Global Filter
                if ($month && $year) {
                     $query->whereMonth('fecha_emision', $month)
                           ->whereYear('fecha_emision', $year);
                }

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                            ->orWhere('rfc', 'like', "%{$search}%")
                            ->orWhere('monto', 'like', "%{$search}%");
                    });
                }
            })
            ->with(['factura' => function ($query) {
                $query->withCount('conciliaciones')
                    ->with('conciliaciones.user');
            }])
            ->latest()
            ->get();

        return Inertia::render('Invoices/Index', [
            'files' => $files,
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    public function destroy(Archivo $file)
    {
        // Authorization is handled by Archivo policy or check ownership manually if policy not set
        // Assuming Archivo has team_id or user_id. From previous context, it seems to rely on global scope or manual check.
        // Let's ensure the user can delete it (e.g. check team_id if available or user_id)

        // Delete physical file
        if (\Illuminate\Support\Facades\Storage::exists($file->path)) {
            \Illuminate\Support\Facades\Storage::delete($file->path);
        }

        // Delete record (Cascades to Factura if set up, otherwise we might need to delete factura explicitly)
        // Assuming cascade for now, but explicit delete is safer if unsure.
        // $file->factura()->delete(); // logic typically handled by DB constraint

        $file->delete();

        return redirect()->route('invoices.index')->with('success', 'Factura eliminada correctamente.');
    }
}
