$conciliaciones = App\Models\Conciliacion::where('monto_aplicado', 0)->get();
foreach ($conciliaciones as $c) {
    if ($c->movimiento && $c->factura) {
        $c->monto_aplicado = min($c->factura->monto, $c->movimiento->monto);
        $c->save();
        echo "Updated conciliacion {$c->id} to {$c->monto_aplicado}\n";
    }
}
