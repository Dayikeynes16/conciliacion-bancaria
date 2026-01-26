<?php

namespace App\Services\Reconciliation;

use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Conciliacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatcherService
{
    /**
     * Find exact matches for a given team.
     * Matches where Amount is identical (or within tolerance) and Date is close.
     */
    public function findMatches(int $teamId, float $tolerance = 0.5): array
    {
        $unreconciledInvoices = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->get();

        $unreconciledMovements = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                      ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->get();

        $matches = [];

        foreach ($unreconciledInvoices as $invoice) {
            foreach ($unreconciledMovements as $movement) {
                // Check Amount Tolerance
                if (abs($invoice->monto - $movement->monto) <= $tolerance) {
                    
                    // Check Date Proximity (e.g. Movement is same day or after Invoice, within 30 days)
                    // Movements usually happen after invoice date.
                    $daysDiff = $invoice->fecha_emision->diffInDays($movement->fecha, false);
                    
                    if ($daysDiff >= -5 && $daysDiff <= 60) { // Allow 5 days before (errors) and 60 days after
                         $matches[] = [
                             'invoice' => $invoice,
                             'movement' => $movement,
                             'score' => 100 - abs($daysDiff), // Higher score = closer date
                             'difference' => abs($invoice->monto - $movement->monto)
                         ];
                    }
                }
            }
        }

        // Sort by score desc
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        return $matches;
    }

    /**
     * Execute a reconciliation match.
     */
    public function reconcile(array $invoiceIds, array $movementIds, string $type = 'manual'): void
    {
        DB::transaction(function () use ($invoiceIds, $movementIds, $type) {
            $invoices = Factura::findMany($invoiceIds);
            $movements = Movimiento::findMany($movementIds);
            
            // Calculate totals
            $totalInvoices = $invoices->sum('monto');
            $totalMovements = $movements->sum('monto');

            // Logic: We create one Conciliacion record per pair? 
            // Or one Conciliacion record linking M invoices to N movements?
            // The schema 'conciliacions' has 'factura_id' and 'movimiento_id' as Foreign Keys.
            // This implies Many-to-Many via the table itself (Association Entity).
            
            // Strategy: Link every invoice to every movement proportionally?
            // Or if it's 1-to-1, simple.
            // If 1-to-N (1 Invoice, 2 Payments): Link Invoice to Pay1, Invoice to Pay2.
            
            foreach ($invoices as $invoice) {
                foreach ($movements as $movement) {
                     // Distribute amount? 
                     // For MVP, just create the link. The intersection means "This invoice is paid by this movement".
                     
                     // If we have 1 Invoice ($100) and 2 Movements ($50, $50).
                     // We link Inv-Mov1 ($50 applied) and Inv-Mov2 ($50 applied).
                     // But we don't know the exact split if not provided.
                     // We can assume equal distribution or FIFO.
                     
                     // Simplest for now: create the record.
                     // But we need 'monto_aplicado'.
                     
                     // Case 1-1:
                     if ($invoices->count() == 1 && $movements->count() == 1) {
                         Conciliacion::create([
                             'user_id' => $invoice->user_id ?? auth()->id(), // Fallback
                             'team_id' => $invoice->team_id ?? auth()->user()->team_id,
                             'factura_id' => $invoice->id,
                             'movimiento_id' => $movement->id,
                             'monto_aplicado' => min($invoice->monto, $movement->monto),
                             'tipo' => $type,
                             'estatus' => 'conciliado',
                         ]);
                     } else {
                         // N-M Complex case.
                         // Only handle 1-N or N-1 for simplicity in MVP Logic or just link them all with 0 to indicate "Group Match".
                         // Better: Create link for each pair.
                         Conciliacion::create([
                             'user_id' => $invoice->user_id ?? auth()->id(),
                             'team_id' => $invoice->team_id ?? auth()->user()->team_id,
                             'factura_id' => $invoice->id,
                             'movimiento_id' => $movement->id,
                             'monto_aplicado' => 0, // Placeholder for valid N-M support
                             'tipo' => $type,
                             'estatus' => 'conciliado',
                         ]);
                     }
                }
            }
        });
    }
}
