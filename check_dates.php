<?php

use App\Models\Conciliacion;

$latest = Conciliacion::orderBy('id', 'desc')->take(5)->get();

foreach ($latest as $c) {
    echo "ID: {$c->id} | Created: {$c->created_at} | Conciliacion At: {$c->fecha_conciliacion}\n";
}
