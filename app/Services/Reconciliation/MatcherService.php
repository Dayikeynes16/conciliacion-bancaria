<?php

namespace App\Services\Reconciliation;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatcherService
{
    /**
     * Find exact matches for a given team within a specific Month/Year.
     * Matches where Amount is identical (or within tolerance) and Date is in the SAME Month.
     */
    public function findMatches(int $teamId, float $toleranceAmount = 0.5, int $month, int $year): array
    {
        // Optimization: Pre-filter by Month/Year in DB
        $unreconciledInvoices = Factura::where('team_id', $teamId)
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->doesntHave('conciliaciones')
            ->get();

        $unreconciledMovements = Movimiento::where('team_id', $teamId)
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
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
                $diffAmount = abs($invoice->monto - $movement->monto);
                
                if ($diffAmount <= $toleranceAmount) {
                    
                    // Strict Month Check (Verified by DB filter, but good for safety)
                    // If DB filter is active, this is guaranteed.
                    // Score is irrelevant if strict, but we can still use day proximity for sorting dupes.
                    
                    $daysDiff = $invoice->fecha_emision->diffInDays($movement->fecha, false);
                    
                    Log::info("Match Found (Strict Month): Inv {$invoice->id} vs Mov {$movement->id}. Diff: \${$diffAmount}. Days: {$daysDiff}");

                    $matches[] = [
                        'invoice' => $invoice,
                        'movement' => $movement,
                        // Score calculation:
                        // 100 base. Minus days diff.
                        // Since same month, max diff is ~31.
                        'score' => 100 - abs($daysDiff), 
                        'difference' => $diffAmount,
                    ];
                }
            }
        }

        // Sort by score desc (closest date first)
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Filter duplicates: Ensure each Invoice and each Movement is only used once.
        $uniqueMatches = [];
        $usedInvoiceIds = [];
        $usedMovementIds = [];

        foreach ($matches as $match) {
            $invId = $match['invoice']->id;
            $movId = $match['movement']->id;

            if (!in_array($invId, $usedInvoiceIds) && !in_array($movId, $usedMovementIds)) {
                $uniqueMatches[] = $match;
                $usedInvoiceIds[] = $invId;
                $usedMovementIds[] = $movId;
            }
        }

        return $uniqueMatches;
    }

    /**
     * Execute a reconciliation match.
     */
    public function reconcile(array $invoiceIds, array $movementIds, string $type = 'manual', ?string $date = null): void
    {
        DB::transaction(function () use ($invoiceIds, $movementIds, $type, $date) {
            $teamId = auth()->user()->current_team_id;
            $groupId = \Illuminate\Support\Str::uuid();

            $invoices = Factura::where('team_id', $teamId)->findMany($invoiceIds);
            $movements = Movimiento::where('team_id', $teamId)->findMany($movementIds);

            if ($invoices->count() !== count($invoiceIds) || $movements->count() !== count($movementIds)) {
                // If counts don't match, some IDs were invalid or belong to another team
               throw new \Exception('Invalid or unauthorized records selected.');
            }

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
                            'group_id' => $groupId,
                            'user_id' => auth()->id(),
                            'team_id' => auth()->user()->current_team_id,
                            'factura_id' => $invoice->id,
                            'movimiento_id' => $movement->id,
                            'monto_aplicado' => min($invoice->monto, $movement->monto),
                            'tipo' => $type,

                            'estatus' => 'conciliado',
                            'fecha_conciliacion' => $date ?? now(),
                        ]);
                    } else {
                        // N-M Complex case or 1-N / N-1.
                        // For MVP, we attempt to distribute or just assign the movement amount if it's less than invoice,
                        // or invoice amount if less than movement.
                        // A safe default for "Partial" is min(invoice, movement).

                        Conciliacion::create([
                            'group_id' => $groupId,
                            'user_id' => auth()->id(),
                            'team_id' => auth()->user()->current_team_id,
                            'factura_id' => $invoice->id,
                            'movimiento_id' => $movement->id,
                            'monto_aplicado' => min($invoice->monto, $movement->monto),
                            'tipo' => $type,

                            'estatus' => 'conciliado',
                            'fecha_conciliacion' => $date ?? now(),
                        ]);
                    }
                }
            }
        });
    }
}
