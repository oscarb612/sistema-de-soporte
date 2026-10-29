<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $order->code }}</title>
    <style>
        @page { size:A4; margin:12mm; }
        * { box-sizing:border-box; }
        body { margin:0; color:#111827; background:#e5e7eb; font-family:Arial, sans-serif; font-size:12px; line-height:1.35; }
        .toolbar { position:sticky; top:0; z-index:5; display:flex; justify-content:center; gap:10px; padding:12px; background:#111827; }
        .toolbar button, .toolbar a { padding:9px 16px; border:0; border-radius:5px; color:#111827; background:#fff; font-weight:700; text-decoration:none; cursor:pointer; }
        .sheet { width:210mm; min-height:297mm; margin:16px auto; padding:12mm; background:#fff; }
        .header { display:flex; justify-content:space-between; gap:20px; padding-bottom:10px; border-bottom:2px solid #1f2937; }
        .header h1 { margin:0 0 4px; font-size:21px; }
        .header p { margin:2px 0; }
        .copy-label { align-self:flex-start; padding:6px 10px; border:1px solid #111827; font-weight:700; text-transform:uppercase; }
        .section { margin-top:12px; break-inside:avoid; }
        .section h2 { margin:0 0 6px; padding:5px 7px; background:#dbeafe; border:1px solid #93c5fd; font-size:13px; text-transform:uppercase; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 18px; }
        .field { margin:2px 0; }
        .field strong { color:#374151; }
        .device { margin-top:7px; padding:8px; border:1px solid #9ca3af; break-inside:avoid; }
        .device:first-of-type { margin-top:0; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:6px; border:1px solid #9ca3af; text-align:left; vertical-align:top; }
        th { background:#f3f4f6; }
        .money { text-align:right; white-space:nowrap; }
        .totals { width:48%; margin:7px 0 0 auto; }
        .totals td:first-child { font-weight:700; }
        .total-row { font-size:14px; font-weight:700; }
        .notice { padding:9px; border:1px solid #9ca3af; background:#f9fafb; }
        .terms { margin:5px 0 0; padding-left:18px; font-size:10px; }
        .terms li { margin:2px 0; }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:24px; break-inside:avoid; }
        .signature-box { min-height:100px; padding:8px; border:1px solid #9ca3af; }
        .line { display:inline-block; min-width:160px; height:17px; border-bottom:1px solid #111827; vertical-align:bottom; }
        .signature-line { margin-top:30px; padding-top:5px; border-top:1px solid #111827; text-align:center; }
        .footer { margin-top:10px; text-align:center; color:#4b5563; font-size:10px; }
        @media print {
            body { background:#fff; }
            .toolbar { display:none; }
            .sheet { width:auto; min-height:auto; margin:0; padding:0; }
            .sheet + .sheet { break-before:page; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir dos copias</button>
    <a href="{{ route('repair-orders.show', $order) }}">Volver a la orden</a>
</div>

@php
    $orderDevices = $order->devices->isNotEmpty() ? $order->devices : collect([$order->device])->filter();
    $paidTotal = (float) $order->payments->sum('amount');
    $balanceTotal = max((float) $order->total_amount - $paidTotal, 0);
@endphp

@foreach (['Copia oficina', 'Copia cliente'] as $copyLabel)
<article class="sheet">
    <header class="header">
        <div>
            <h1>Comprobante de recepción de equipo</h1>
            <p><strong>{{ $order->company->name }}</strong></p>
            <p>RUT: {{ $order->company->formatted_rut ?: 'No informado' }}</p>
            <p>{{ $order->company->address }} {{ $order->company->phone ? ' · '.$order->company->phone : '' }}</p>
        </div>
        <div>
            <div class="copy-label">{{ $copyLabel }}</div>
            <p><strong>Orden:</strong> {{ $order->code }}</p>
            <p><strong>Ingreso:</strong> {{ $order->created_at->format('d-m-Y H:i') }}</p>
            <p><strong>Modalidad:</strong> {{ $order->service_mode_label }}</p>
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
        <h2>Equipos y accesorios recibidos</h2>
        @foreach ($orderDevices as $device)
            <div class="device">
                <strong>Equipo {{ $loop->iteration }}</strong>
                <div class="grid">
                    <p class="field"><strong>Tipo:</strong> {{ $device->type }}</p>
                    <p class="field"><strong>Marca:</strong> {{ $device->brand ?: 'No informada' }}</p>
                    <p class="field"><strong>Modelo:</strong> {{ $device->model ?: 'No informado' }}</p>
                    <p class="field"><strong>N.º de serie:</strong> {{ $device->serial_number ?: 'No informado' }}</p>
                    <p class="field"><strong>Accesorios:</strong> {{ $device->accessories ?: 'Sin accesorios declarados' }}</p>
                    <p class="field"><strong>Estado físico:</strong> {{ $device->physical_condition ?: 'Sin observaciones' }}</p>
                </div>
            </div>
        @endforeach
    </section>

    @if ($order->service_mode === 'precio_definido')
        <section class="section">
            <h2>Servicio contratado y valor aceptado</h2>
            <table>
                <thead><tr><th>Servicio</th><th>Cantidad</th><th class="money">Precio neto</th><th class="money">Subtotal neto</th></tr></thead>
                <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->description }}@if($item->service?->description)<br><small>{{ $item->service->description }}</small>@endif</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="money">${{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                        <td class="money">${{ number_format((float) $item->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <table class="totals">
                <tr><td>Subtotal</td><td class="money">${{ number_format((float) $order->labor_amount, 0, ',', '.') }}</td></tr>
                <tr><td>Descuento</td><td class="money">-${{ number_format((float) $order->discount_amount, 0, ',', '.') }}</td></tr>
                <tr><td>Neto</td><td class="money">${{ number_format((float) $order->net_amount, 0, ',', '.') }}</td></tr>
                <tr><td>IVA (19%)</td><td class="money">${{ number_format((float) $order->tax_amount, 0, ',', '.') }}</td></tr>
                <tr class="total-row"><td>Total</td><td class="money">${{ number_format((float) $order->total_amount, 0, ',', '.') }}</td></tr>
                <tr><td>Abonado</td><td class="money">${{ number_format($paidTotal, 0, ',', '.') }}</td></tr>
                <tr><td>Saldo</td><td class="money">${{ number_format($balanceTotal, 0, ',', '.') }}</td></tr>
            </table>
            <p class="notice">El cliente declara conocer y aceptar el servicio solicitado, su alcance y el valor total indicado en esta orden.</p>
        </section>
    @else
        @if($order->items->isNotEmpty())
        <section class="section">
            <h2>Servicio de retiro y entrega</h2>
            <table><thead><tr><th>Ítem</th><th>Cantidad</th><th class="money">Valor neto</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{$item->description}}</td><td>{{$item->quantity}}</td><td class="money">${{number_format((float)$item->total,0,',','.')}}</td></tr>@endforeach</tbody></table>
            <table class="totals"><tr><td>Neto</td><td class="money">${{number_format((float)$order->net_amount,0,',','.')}}</td></tr><tr><td>IVA (19%)</td><td class="money">${{number_format((float)$order->tax_amount,0,',','.')}}</td></tr><tr class="total-row"><td>Total traslado</td><td class="money">${{number_format((float)$order->total_amount,0,',','.')}}</td></tr></table>
        </section>
        @endif
        <section class="section">
            <h2>Solicitud y autorización de diagnóstico</h2>
            <p><strong>Problema informado:</strong> {{ $order->reported_problem ?: 'Sin información' }}</p>
            <p><strong>Observaciones iniciales:</strong> {{ $order->initial_observations ?: 'Sin observaciones' }}</p>
            <p class="notice">El equipo se recibe para diagnóstico. El valor final de la reparación será informado mediante un presupuesto y requerirá aprobación del cliente antes de iniciar los trabajos. Esta recepción no implica la aprobación de una reparación.</p>
        </section>
    @endif

    <section class="section">
        <h2>Condiciones de recepción</h2>
        <ul class="terms">
            <li>El cliente confirma que los equipos, accesorios y condiciones indicados en este comprobante son correctos.</li>
            <li>La empresa no responde por accesorios que no hayan sido declarados al momento de la recepción.</li>
            <li>El cliente es responsable de mantener un respaldo de su información. Algunos trabajos, especialmente el formateo, pueden eliminar datos.</li>
            <li>El equipo se entregará al titular, a una persona autorizada o a quien presente este comprobante y acredite su identidad.</li>
            <li>Una vez enviado el diagnóstico al cliente, el equipo se mantendrá en custodia por un plazo máximo de 30 días. Si no es retirado dentro de ese período, el equipo pasará a reciclaje.</li>
            <li>El diagnóstico tiene un valor final de ${{ number_format($order->company->diagnostic_fee_gross ?? 20000,0,',','.') }} y un plazo de {{ $order->company->technical_diagnostic_hours ?? 24 }} horas hábiles. Este valor se bonifica si el presupuesto de reparación es aprobado; si es rechazado, deberá pagarse junto con los servicios de traslado realizados.</li>
        </ul>
    </section>

    <section class="signatures">
        <div class="signature-box">
            @if($order->homeServicePreorder?->signature_path)
                <p><strong>Firmado por:</strong> {{$order->homeServicePreorder->signer_name}} · {{$order->homeServicePreorder->signer_rut}}</p>
                <img src="{{route('home-service.signature',$order->homeServicePreorder)}}" alt="Firma" style="width:100%;height:55px;object-fit:contain">
                <div class="signature-line">Firma digital de recepción · {{$order->homeServicePreorder->signed_at?->format('d-m-Y H:i')}}</div>
            @else
                <p>Nombre cliente: <span class="line"></span></p><p>RUT: <span class="line"></span></p><div class="signature-line">Firma del cliente</div>
            @endif
        </div>
        <div class="signature-box">
            <p>Recibido por: <span class="line"></span></p>
            <p>Fecha: <span class="line"></span></p>
            <div class="signature-line">Firma de recepción</div>
        </div>
    </section>

    <footer class="footer">Documento generado para la orden {{ $order->code }} · {{ $copyLabel }}</footer>
</article>
@endforeach
</body>
</html>
