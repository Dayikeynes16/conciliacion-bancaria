<table>
    <thead>
    <tr>
        <th>Fecha Emisión</th>
        <th>Factura</th>
        <th>RFC</th>
        <th>UUID</th>
        <th>Monto</th>
    </tr>
    </thead>
    <tbody>
    @foreach($invoices as $invoice)
        <tr>
            <td>{{ $invoice->fecha_emision ? \Carbon\Carbon::parse($invoice->fecha_emision)->format('d/m/Y') : 'N/A' }}</td>
            <td>{{ $invoice->nombre }}</td>
            <td>{{ $invoice->rfc }}</td>
            <td>{{ $invoice->uuid }}</td>
            <td>{{ $invoice->monto }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
