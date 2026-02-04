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
        $date = $request->input('date');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $query = Archivo::query()
            ->select('archivos.*')
            ->join('facturas', 'archivos.id', '=', 'facturas.file_id_xml')
            ->where('archivos.team_id', $request->user()->current_team_id)
            ->with(['factura' => function ($q) {
                $q->withCount('conciliaciones')
                    ->with('conciliaciones.user');
            }])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('archivos.original_name', 'like', "%{$search}%")
                        ->orWhere('archivos.checksum', 'like', "%{$search}%")
                        ->orWhere('facturas.nombre', 'like', "%{$search}%")
                        ->orWhere('facturas.rfc', 'like', "%{$search}%")
                        ->orWhere('facturas.monto', 'like', "%{$search}%");
                });
            })
            ->when($date, function ($q) use ($date) {
                return $q->whereDate('facturas.fecha_emision', $date);
            })
            ->when((! $date && $month), function ($q) use ($month) {
                return $q->whereMonth('facturas.fecha_emision', $month);
            })
            ->when((! $date && $year), function ($q) use ($year) {
                return $q->whereYear('facturas.fecha_emision', $year);
            });

        // Apply Sorting
        if ($sort === 'total') {
            $query->orderBy('facturas.monto', $direction);
        } elseif ($sort === 'fecha_emision') {
            $query->orderBy('facturas.fecha_emision', $direction);
        } else {
            // Default sort, usually by upload date (created_at of archivo)
            // Use table alias to avoid ambiguity if sort is created_at
            $sortField = $sort === 'created_at' ? 'archivos.created_at' : $sort;
            $query->orderBy($sortField, $direction);
        }

        $perPageParam = $request->input('per_page', 10);
        $perPage = ($perPageParam === 'all') ? 10000 : $perPageParam;

        $files = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Invoices/Index', [
            'files' => $files,
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
                'date' => $date,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPageParam,
            ],
        ]);
    }

    public function destroy(Archivo $file)
    {
        if ($file->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

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

    public function batchDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se han seleccionado facturas.');
        }

        // Fetch files ensuring they belong to the current team
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

        return redirect()->route('invoices.index')->with('success', "Se han eliminado {$count} facturas correctamente.");
    }
}
