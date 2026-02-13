<table>
    <thead>
    <tr>
        <th>Fecha Conciliación</th>
        <th>Usuario</th>
        <th>Movimiento</th>
        <th>Referencia</th>
        <th>Monto Movimiento</th>
        <th>Factura Asociada</th>
        <th>Monto Factura</th>
    </tr>
    </thead>
    <tbody>
    @foreach($movements as $movement)
        @foreach($movement->conciliaciones as $conciliacion)
            <tr>
                <td>{{ $conciliacion->created_at->format('d/m/Y') }}</td>
                <td>{{ $conciliacion->user->name }}</td>
                <td>{{ $movement->descripcion }}</td>
                <td>{{ $movement->referencia }}</td>
                <td>{{ $movement->monto }}</td>
                <td>{{ $conciliacion->factura->nombre ?? 'N/A' }}</td>
                <td>{{ $conciliacion->factura->monto ?? '0.00' }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
