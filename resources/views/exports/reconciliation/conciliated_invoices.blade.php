<table>
    <thead>
    <tr>
        <th>Fecha Conciliación</th>
        <th>Usuario</th>
        <th>Factura</th>
        <th>RFC</th>
        <th>Monto Factura</th>
        <th>Movimiento</th>
        <th>Referencia</th>
        <th>Monto Movimiento</th>
        <th>Diferencia</th>
    </tr>
    </thead>
    <tbody>
    @foreach($invoices as $invoice)
        @foreach($invoice->conciliaciones as $conciliacion)
            <tr>
                <td>{{ $conciliacion->created_at->format('d/m/Y') }}</td>
                <td>{{ $conciliacion->user->name }}</td>
                <td>{{ $invoice->nombre }}</td>
                <td>{{ $invoice->rfc }}</td>
                <td>{{ $invoice->monto }}</td>
                <td>{{ $conciliacion->movimiento->descripcion }}</td>
                <td>{{ $conciliacion->movimiento->referencia }}</td>
                <td>{{ $conciliacion->movimiento->monto }}</td>
                <td>{{ $invoice->monto - $conciliacion->movimiento->monto }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
