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
    public function findMatches(int $teamId, float $toleranceAmount, int $month, int $year): array
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

            if (! in_array($invId, $usedInvoiceIds) && ! in_array($movId, $usedMovementIds)) {
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

            // Calculate total available amounts to prevent over-application
            $invoiceRemaining = [];
            foreach ($invoices as $inv) {
                $invoiceRemaining[$inv->id] = $inv->monto;
            }

            $movementRemaining = [];
            foreach ($movements as $mov) {
                $movementRemaining[$mov->id] = $mov->monto;
            }

            foreach ($invoices as $invoice) {
                // If invoice is fully paid, skip
                if ($invoiceRemaining[$invoice->id] <= 0) {
                    continue;
                }

                foreach ($movements as $movement) {
                    // If movement is fully used, skip
                    if ($movementRemaining[$movement->id] <= 0) {
                        continue;
                    }

                    // Determine match amount based on remaining balances
                    $amountToApply = min($invoiceRemaining[$invoice->id], $movementRemaining[$movement->id]);

                    if ($amountToApply > 0) {
                        Conciliacion::create([
                            'group_id' => $groupId,
                            'user_id' => auth()->id(),
                            'team_id' => auth()->user()->current_team_id,
                            'factura_id' => $invoice->id,
                            'movimiento_id' => $movement->id,
                            'monto_aplicado' => $amountToApply,
                            'tipo' => $type,
                            'estatus' => 'conciliado',
                            'fecha_conciliacion' => $date ?? now(),
                        ]);

                        // Deduct applied amount from both sides
                        $invoiceRemaining[$invoice->id] -= $amountToApply;
                        $movementRemaining[$movement->id] -= $amountToApply;

                        // Stop checking movements for this invoice if it's fully paid
                        if ($invoiceRemaining[$invoice->id] <= 0) {
                            break;
                        }
                    }
                }
            }
        });
    }
}
