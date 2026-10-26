<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quote->quote_type === 'closure' ? 'Liquidación de cierre' : 'Presupuesto' }} {{ $quote->quote_number }}</title>
    <style>
        @page { size:A4; margin:14mm; }
        * { box-sizing:border-box; }
        body { margin:0; color:#111827; background:#e5e7eb; font-family:Arial, sans-serif; font-size:12px; line-height:1.4; }
        .toolbar { position:sticky; top:0; z-index:5; display:flex; justify-content:center; gap:10px; padding:12px; background:#111827; }
        .toolbar button, .toolbar a { padding:9px 16px; border:0; border-radius:5px; color:#111827; background:#fff; font-weight:700; text-decoration:none; cursor:pointer; }
        .sheet { width:210mm; min-height:297mm; margin:16px auto; padding:14mm; background:#fff; }
        .header { display:flex; justify-content:space-between; gap:24px; padding-bottom:12px; border-bottom:2px solid #1f2937; }
        .header h1 { margin:0 0 5px; font-size:23px; }
        .header p { margin:2px 0; }
        .document-data { min-width:210px; padding:9px; border:1px solid #9ca3af; }
        .copy-label { margin-bottom:6px; font-weight:700; text-align:center; text-transform:uppercase; }
        .section { margin-top:14px; break-inside:avoid; }
        .section h2 { margin:0 0 7px; padding:6px 8px; border:1px solid #93c5fd; background:#dbeafe; font-size:13px; text-transform:uppercase; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 20px; }
        .field { margin:2px 0; }
        .diagnosis { min-height:66px; padding:10px; border:1px solid #9ca3af; white-space:pre-wrap; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:7px; border:1px solid #9ca3af; text-align:left; vertical-align:top; }
        th { background:#f3f4f6; }
        .money { text-align:right; white-space:nowrap; }
        .totals { width:48%; margin:10px 0 0 auto; }
        .totals td:first-child { font-weight:700; }
        .total-row { font-size:15px; font-weight:700; background:#dbeafe; }
        .notes { padding:9px; border:1px solid #9ca3af; background:#f9fafb; }
        .footer { margin-top:28px; padding-top:8px; border-top:1px solid #9ca3af; color:#4b5563; font-size:10px; text-align:center; }
        @media print {
            body { background:#fff; }
            .toolbar { display:none; }
            .sheet { width:auto; min-height:auto; margin:0; padding:0; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir copia cliente</button>
    <a href="{{ route('repair-quotes.show', $quote) }}">Volver al presupuesto</a>
</div>

@php
    $order = $quote->repairOrder;
    $devices = $order->devices->isNotEmpty() ? $order->devices : collect([$order->device])->filter();
@endphp

<article class="sheet">
    <header class="header">
        <div>
            <h1>{{ $quote->quote_type === 'closure' ? 'Liquidación de cierre de servicio técnico' : 'Presupuesto de servicio técnico' }}</h1>
            <p><strong>{{ $quote->company->name }}</strong></p>
            <p>RUT: {{ $quote->company->formatted_rut ?: 'No informado' }}</p>
            <p>{{ $quote->company->address ?: 'Dirección no informada' }}</p>
            <p>{{ $quote->company->phone ?: 'Teléfono no informado' }}{{ $quote->company->email ? ' · '.$quote->company->email : '' }}</p>
        </div>
        <div class="document-data">
            <div class="copy-label">Copia cliente</div>
            <p><strong>Presupuesto:</strong> {{ $quote->quote_number }}</p>
            <p><strong>Orden:</strong> {{ $order->code }}</p>
            <p><strong>Fecha:</strong> {{ $quote->created_at->format('d-m-Y') }}</p>
            <p><strong>Válido hasta:</strong> {{ $quote->valid_until?->format('d-m-Y') ?? 'Sin fecha límite' }}</p>
            <p><strong>Estado:</strong> {{ $quote->status_label }}</p>
        </div>
    </header>

    <section class="section">
        <h2>Datos del cliente</h2>
        <div class="grid">
            <p class="field"><strong>Nombre:</strong> {{ $order->client->name }}</p>
            <p class="field"><strong>RUT:</strong> {{ $order->client->formatted_rut }}</p>
            <p class="field"><strong>Teléfono:</strong> {{ $order->client->phone ?: 'No informado' }}</p>
            <p class="field"><strong>Correo:</strong> {{ $order->client->email ?: 'No informado' }}</p>
            <p class="field" style="grid-column:1/-1;"><strong>Dirección:</strong> {{ $order->client->address ?: 'No informada' }}</p>
            <p class="field"><strong>Región:</strong> {{ $order->client->region ?: 'No informada' }}</p>
            <p class="field"><strong>Comuna:</strong> {{ $order->client->commune ?: 'No informada' }}</p>
        </div>
    </section>

    <section class="section">
        <h2>Equipos relacionados</h2>
        <table>
            <thead><tr><th>Tipo</th><th>Marca</th><th>Modelo</th><th>N.º de serie</th></tr></thead>
            <tbody>
            @foreach ($devices as $device)
                <tr>
                    <td>{{ $device->type }}</td>
                    <td>{{ $device->brand ?: 'No informada' }}</td>
                    <td>{{ $device->model ?: 'No informado' }}</td>
                    <td>{{ $device->serial_number ?: 'No informado' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Diagnóstico</h2>
        <div class="diagnosis">{{ $quote->diagnosis_summary }}</div>
    </section>

    <section class="section">
        <h2>Detalle de valores</h2>
        <table>
            <thead>
            <tr><th>Tipo</th><th>Descripción</th><th>Cantidad</th><th class="money">Precio unitario neto</th><th class="money">Subtotal neto</th></tr>
            </thead>
            <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->type)) }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="money">${{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="money">${{ number_format((float) $item->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Mano de obra / servicios</td><td class="money">${{ number_format((float) $quote->labor_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Repuestos</td><td class="money">${{ number_format((float) $quote->parts_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Descuento</td><td class="money">-${{ number_format((float) $quote->discount_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Neto</td><td class="money">${{ number_format((float) $quote->net_amount, 0, ',', '.') }}</td></tr>
            <tr><td>IVA (19%)</td><td class="money">${{ number_format((float) $quote->tax_amount, 0, ',', '.') }}</td></tr>
            <tr class="total-row"><td>Total</td><td class="money">${{ number_format((float) $quote->total_amount, 0, ',', '.') }}</td></tr>
        </table>
    </section>

    <section class="section">
        <p class="notes">Este presupuesto corresponde al diagnóstico y trabajos detallados. Cualquier servicio o repuesto adicional deberá ser informado y aprobado por el cliente.</p>
    </section>

    <footer class="footer">Documento emitido para el cliente · Presupuesto {{ $quote->quote_number }} · Orden {{ $order->code }}</footer>
</article>
</body>
</html>
