<?php

namespace Tests\Unit;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Team;
use App\Models\User;
use App\Services\Reconciliation\MatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_correctly_allocates_amounts_in_many_to_many_scenario()
    {
        // Setup
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->forceFill(['current_team_id' => $team->id])->save();
        $this->actingAs($user);

        // Scenario: 2 Invoices of $100 and 2 Movements of $100
        // Total Debt: $200. Total Paid: $200.
        // Expected Applied: $200.
        // Bugged Applied: $400 (if naive N*M loop).

        $inv1 = Factura::factory()->create(['team_id' => $team->id, 'monto' => 100]);
        $inv2 = Factura::factory()->create(['team_id' => $team->id, 'monto' => 100]);

        $mov1 = Movimiento::factory()->create(['team_id' => $team->id, 'monto' => 100]);
        $mov2 = Movimiento::factory()->create(['team_id' => $team->id, 'monto' => 100]);

        $matcher = new MatcherService;
        $matcher->reconcile(
            [$inv1->id, $inv2->id],
            [$mov1->id, $mov2->id],
            'manual'
        );

        $totalApplied = Conciliacion::sum('monto_aplicado');

        // Assert
        $this->assertEquals(200, $totalApplied, "Total applied amount should be 200, but got {$totalApplied}");

        // Verify individual allocation (optional, but good sanity check)
        // Inv1 should have 100 applied max.
        $inv1Applied = Conciliacion::where('factura_id', $inv1->id)->sum('monto_aplicado');
        $this->assertEquals(100, $inv1Applied, 'Invoice 1 over-applied');
    }
}
