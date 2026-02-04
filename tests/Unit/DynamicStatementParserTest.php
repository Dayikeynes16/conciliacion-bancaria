<?php

namespace Tests\Unit;

use App\Models\BankFormat;
use App\Models\Team;
use App\Models\User;
use App\Services\Parsers\DynamicStatementParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DynamicStatementParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_separate_debit_credit_columns()
    {
        // Setup User and Team
        $user = User::factory()->create();
        $team = Team::create([
            'user_id' => $user->id,
            'personal_team' => true,
            'name' => 'Test Team',
            'rfc' => 'TEST12345678', // Provide dummy RFC if required
        ]);

        // Setup Bank Format with Separate Columns
        // A=Date, B=Desc, C=Debit, D=Credit
        $format = BankFormat::create([
            'team_id' => $team->id,
            'name' => 'Debit Credit Format',
            'start_row' => 1,
            'date_column' => 'A',
            'description_column' => 'B',
            'amount_column' => null,
            'debit_column' => 'C',
            'credit_column' => 'D',
        ]);

        $parser = new DynamicStatementParser($format);

        // Mock Data
        // Row 1: Cargo (Debit) 100
        // Row 2: Abono (Credit) 500
        $rows = new Collection([
            ['2023-01-01', 'Payment', '100.00', ''],
            ['2023-01-02', 'Deposit', '', '500.00'],
        ]);

        $parsed = $this->invokeMethod($parser, 'normalize', [$rows]);

        $this->assertCount(2, $parsed);

        // Check Row 1
        $row1 = $parsed->first();
        $this->assertEquals('2023-01-01', $row1['fecha']);
        $this->assertEquals(100.00, $row1['monto']);
        $this->assertEquals('cargo', $row1['tipo']);

        // Check Row 2
        $row2 = $parsed->last();
        $this->assertEquals('2023-01-02', $row2['fecha']);
        $this->assertEquals(500.00, $row2['monto']);
        $this->assertEquals('abono', $row2['tipo']);
    }

    // Helper to call protected method
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
