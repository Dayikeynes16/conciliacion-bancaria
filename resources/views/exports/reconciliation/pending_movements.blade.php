<table>
    <thead>
    <tr>
        <th>Fecha</th>
        <th>Descripción</th>
        <th>Referencia</th>
        <th>Monto</th>
    </tr>
    </thead>
    <tbody>
    @foreach($movements as $movement)
        <tr>
            <td>{{ $movement->fecha ? \Carbon\Carbon::parse($movement->fecha)->format('d/m/Y') : 'N/A' }}</td>
            <td>{{ $movement->descripcion }}</td>
            <td>{{ $movement->referencia }}</td>
            <td>{{ $movement->monto }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
