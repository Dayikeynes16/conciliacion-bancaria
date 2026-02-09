<?php

use App\Models\Movimiento;

$m = Movimiento::latest()->first();
print_r($m->toArray());
echo "\nAttribute fecha: " . $m->fecha . "\n";
