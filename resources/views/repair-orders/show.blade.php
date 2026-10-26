@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Orden {{ $order->code }}</h1>
        <p class="muted">{{ $order->company->name }} · {{ $order->created_at->format('d-m-Y H:i') }}</p>
    </div>
    <div class="actions" style="margin:0;">
        @if ($order->service_mode === 'diagnostico' && $order->technical_diagnosis && $order->quotes->where('quote_type','!=','closure')->where('status','!=','rechazado')->isEmpty())
            <a class="btn primary" href="{{ route('repair-orders.quotes.create', $order) }}">Crear presupuesto</a>
        @elseif ($order->service_mode === 'diagnostico' && $order->quotes->where('quote_type','!=','closure')->where('status','!=','rechazado')->isNotEmpty())
            <span class="btn disabled" aria-disabled="true">Presupuesto creado</span>
        @endif
        <a class="btn" href="{{ route('repair-orders.edit', $order) }}">Editar</a>
        <a class="btn warning" href="{{ route('repair-orders.print', $order) }}" target="_blank">Imprimir comprobante</a>
        @if ($order->client->email)
            <form method="post" action="{{ route('repair-orders.send-email', $order) }}">
                @csrf
                <button class="btn" type="submit">{{ $order->email_status === 'enviado' ? 'Reenviar correo' : 'Enviar correo' }}</button>
            </form>
        @endif
        <form method="post" action="{{ route('repair-orders.destroy', $order) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta orden? Esta acción no se puede deshacer.');">
            @csrf
            @method('delete')
            <button class="btn danger" type="submit">Eliminar</button>
        </form>
    </div>
</div>

@if ($order->service_mode === 'diagnostico' && $order->quotes->where('quote_type','!=','closure')->isEmpty())
<div class="panel" style="margin-top:16px;">
    <h2>{{ $order->technical_diagnosis ? 'Diagnóstico registrado' : 'Registrar diagnóstico' }}</h2>
    <p class="muted">Documenta el resultado de la revisión técnica. Al guardarlo, la orden quedará lista para generar el presupuesto.</p>
    <form method="post" action="{{ route('repair-orders.diagnosis.store', $order) }}">
        @csrf
        <label>Diagnóstico técnico
            <textarea name="technical_diagnosis" required>{{ old('technical_diagnosis', $order->technical_diagnosis) }}</textarea>
        </label>
        @error('technical_diagnosis')<p class="field-error">{{ $message }}</p>@enderror
        <div class="actions"><button class="btn primary" type="submit">{{ $order->technical_diagnosis ? 'Actualizar diagnóstico' : 'Guardar diagnóstico' }}</button></div>
    </form>
</div>
@elseif ($order->technical_diagnosis)
<div class="panel" style="margin-top:16px;">
    <h2>Diagnóstico técnico</h2>
    <p>{!! nl2br(e($order->technical_diagnosis)) !!}</p>
</div>
@endif

<div class="panel" style="margin-top:16px;">
    <h2>Correo y fotografías de recepción</h2>
    <p><strong>Estado del correo:</strong> <span class="badge">{{ ucfirst($order->email_status ?: 'pendiente') }}</span></p>
    @if ($order->emailed_at)<p>Enviado: {{ $order->emailed_at->format('d-m-Y H:i') }} a {{ $order->client->email }}</p>@endif
    @if ($order->email_error)<p class="field-error">{{ $order->email_error }}</p>@endif
    @if ($order->photos->isNotEmpty())
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:12px;">
            @foreach ($order->photos as $photo)
                <a href="{{ route('repair-order-photos.show', $photo) }}" target="_blank">
                    <img src="{{ route('repair-order-photos.show', $photo) }}" alt="Fotografía {{ $loop->iteration }}" style="width:100%; height:140px; object-fit:cover; border-radius:6px; border:1px solid var(--line);">
                    <span class="muted">{{ ucfirst($photo->category) }} · {{ number_format($photo->size / 1024, 0, ',', '.') }} KB</span>
                </a>
            @endforeach
        </div>
    @else
        <p class="muted">Esta orden no tiene fotografías adjuntas.</p>
    @endif
</div>

<div class="details">
    <div class="panel">
        <h2>Cliente</h2>
        <p><strong>{{ $order->client->name }}</strong></p>
        <p>RUT: {{ $order->client->formatted_rut }}</p>
        <p>Teléfono: {{ $order->client->phone }}</p>
        <p>Correo: {{ $order->client->email }}</p>
        <p>Dirección: {{ $order->client->address }}</p>
        <p>Región: {{ $order->client->region ?: 'No informada' }}</p>
        <p>Comuna: {{ $order->client->commune ?: 'No informada' }}</p>
    </div>

    <div class="panel">
        <h2>Equipos</h2>
        @foreach ($order->devices->isNotEmpty() ? $order->devices : collect([$order->device]) as $device)
            <div style="border-bottom:1px solid var(--line); margin-bottom:12px; padding-bottom:12px;">
                <p><strong>Equipo {{ $loop->iteration }}</strong></p>
                <p>Tipo: {{ $device->type }}</p>
                <p>Marca: {{ $device->brand }}</p>
                <p>Modelo: {{ $device->model }}</p>
                <p>Número de serie: {{ $device->serial_number }}</p>
                <p>Accesorios recibidos: {{ $device->accessories }}</p>
                <p>Detalle Técnico: {{ $device->physical_condition }}</p>
            </div>
        @endforeach
    </div>
</div>

<div class="panel" style="margin-top:16px;">
    <h2>Atención</h2>
    <p><strong>Modalidad:</strong> {{ $order->service_mode_label }}</p>
    @if ($order->service_mode === 'precio_definido')
        @foreach ($order->items as $item)
            <p><strong>Servicio:</strong> {{ $item->description }}</p>
            <p><strong>Detalle:</strong> {{ $item->service?->description ?: 'Sin detalle adicional' }}</p>
            <p><strong>Cantidad:</strong> {{ $item->quantity }}</p>
            <p><strong>Precio unitario neto:</strong> ${{ number_format((float) $item->unit_price, 0, ',', '.') }}</p>
            <p><strong>Subtotal neto:</strong> ${{ number_format((float) $item->total, 0, ',', '.') }}</p>
        @endforeach
        <p><strong>Descuento:</strong> ${{ number_format((float) $order->discount_amount, 0, ',', '.') }}</p>
        <p><strong>Neto:</strong> ${{ number_format((float) $order->net_amount, 0, ',', '.') }}</p>
        <p><strong>IVA (19%):</strong> ${{ number_format((float) $order->tax_amount, 0, ',', '.') }}</p>
        <h2>Total: ${{ number_format((float) $order->total_amount, 0, ',', '.') }}</h2>
        <p><strong>Aceptado por el cliente:</strong> {{ $order->customer_accepted_at?->format('d-m-Y H:i') ?? 'No registrado' }}</p>
    @elseif($order->items->isNotEmpty())
        <h3>Traslado a domicilio</h3>
        @foreach($order->items as $item)<p><strong>{{$item->description}}:</strong> ${{number_format((float)$item->total,0,',','.')}} neto</p>@endforeach
        <p><strong>Neto:</strong> ${{number_format((float)$order->net_amount,0,',','.')}} · <strong>IVA:</strong> ${{number_format((float)$order->tax_amount,0,',','.')}} · <strong>Total:</strong> ${{number_format((float)$order->total_amount,0,',','.')}}</p>
    @endif
</div>

@if ($order->service_mode === 'diagnostico')
<div class="panel" style="margin-top:16px;">
    <h2>Presupuestos</h2>
    @if($order->diagnosis_due_at)<p><strong>Diagnóstico comprometido:</strong> {{$order->diagnosis_due_at->format('d-m-Y H:i')}} ({{$order->company->technical_diagnostic_hours ?? 24}} horas hábiles)</p>@endif
    <table>
        <thead>
        <tr>
            <th>Número</th>
            <th>Estado</th>
            <th>Válido hasta</th>
            <th>Total</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($order->quotes as $quote)
            <tr>
                <td>{{ $quote->quote_number }}<br><small>{{ $quote->quote_type === 'closure' ? 'Liquidación de cierre' : 'Reparación' }}</small></td>
                <td><span class="badge">{{ $quote->status_label }}</span></td>
                <td>{{ optional($quote->valid_until)->format('d-m-Y') ?? 'Sin fecha' }}</td>
                <td>${{ number_format($quote->total_amount, 0, ',', '.') }}</td>
                <td>
                    <div class="actions" style="margin:0;">
                        <a class="btn" href="{{ route('repair-quotes.show', $quote) }}">{{ $quote->quote_type === 'closure' ? 'Ver liquidación' : 'Ver presupuesto' }}</a>
                        <form method="post" action="{{ route('repair-quotes.destroy', $quote) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este presupuesto? La orden volverá al estado Diagnosticado.');">
                            @csrf
                            @method('delete')
                            <button class="btn danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Esta orden aún no tiene presupuestos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endif

@php
    $orderDevices = $order->devices->isNotEmpty() ? $order->devices : collect([$order->device]);
    $approvedQuoteTotal = $order->quotes->where('status', 'aprobado')->sum('total_amount');
    $closureTotal = $order->quotes->where('quote_type', 'closure')->where('status', 'emitido')->sum('total_amount');
    $quoteTotal = $approvedQuoteTotal > 0 ? $approvedQuoteTotal : ($closureTotal > 0 ? $closureTotal : (float) optional($order->quotes->where('status','emitido')->sortByDesc('id')->first())->total_amount);
    $budgetTotal = $quoteTotal > 0 ? $quoteTotal : $order->total_amount;
    $paidTotal = $order->payments->sum('amount');
    $balanceTotal = max(0, $budgetTotal - $paidTotal);
@endphp

<div class="panel" style="margin-top:16px;">
    <div class="page-head" style="margin-bottom:14px;">
        <div>
            <h2>Abonos y pagos</h2>
            <p class="muted">Registra pagos posteriores al ingreso y asigna el abono a un equipo específico cuando corresponda.</p>
        </div>
    </div>

    <div class="grid stats" style="margin-bottom:16px;">
        <div class="stat"><span>{{ $order->service_mode === 'precio_definido' ? 'Total acordado' : 'Total presupuesto' }}</span><strong>${{ number_format((float) $budgetTotal, 0, ',', '.') }}</strong></div>
        <div class="stat"><span>Total abonado</span><strong>${{ number_format((float) $paidTotal, 0, ',', '.') }}</strong></div>
        <div class="stat"><span>Saldo pendiente</span><strong>${{ number_format((float) $balanceTotal, 0, ',', '.') }}</strong></div>
        <div class="stat"><span>Abonos</span><strong>{{ $order->payments->count() }}</strong></div>
    </div>

    <form method="post" action="{{ route('repair-orders.payments.store', $order) }}">
        @csrf
        <div class="form-grid">
            <label>Asignar a
                <select name="device_id">
                    <option value="">Orden completa</option>
                    @foreach ($orderDevices as $device)
                        <option value="{{ $device->id }}" @selected((int) old('device_id') === $device->id)>
                            Equipo {{ $loop->iteration }} - {{ $device->type }} {{ $device->brand }} {{ $device->model }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Presupuesto relacionado
                <select name="repair_quote_id">
                    <option value="">Sin presupuesto específico</option>
                    @foreach ($order->quotes as $quote)
                        <option value="{{ $quote->id }}" @selected((int) old('repair_quote_id') === $quote->id)>
                            {{ $quote->quote_number }} - {{ $quote->status_label }} - ${{ number_format((float) $quote->total_amount, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Tipo
                <select name="payment_type" required>
                    @foreach ($paymentTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_type', 'abono') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>Método de pago
                <select name="payment_method" required>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method', 'transferencia') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>Monto
                <input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required>
            </label>

            <label>Fecha del abono
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
            </label>

            <label class="full">Observación
                <textarea name="notes">{{ old('notes') }}</textarea>
            </label>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Registrar abono</button>
        </div>
    </form>

    <table style="margin-top:16px;">
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Asignación</th>
            <th>Tipo</th>
            <th>Método</th>
            <th>Monto</th>
            <th>Usuario</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($order->payments->sortByDesc('payment_date') as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                <td>
                    @if ($payment->device)
                        {{ $payment->device->type }} {{ $payment->device->brand }} {{ $payment->device->model }}
                    @else
                        Orden completa
                    @endif
                    @if ($payment->repairQuote)
                        <br><span class="muted">{{ $payment->repairQuote->quote_number }}</span>
                    @endif
                </td>
                <td>{{ $payment->payment_type_label }}</td>
                <td>{{ $payment->payment_method_label }}</td>
                <td>${{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                <td>{{ $payment->creator->name ?? 'Sin usuario' }}</td>
                <td>
                    <form method="post" action="{{ route('repair-orders.payments.destroy', [$order, $payment]) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este abono?');">
                        @csrf
                        @method('delete')
                        <button class="btn danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
            @if ($payment->notes)
                <tr>
                    <td></td>
                    <td colspan="6"><span class="muted">{{ $payment->notes }}</span></td>
                </tr>
            @endif
        @empty
            <tr><td colspan="7">Esta orden aún no tiene abonos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="panel" style="margin-top:16px;">
    <h2>Historial de estados</h2>
    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Comentario</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->statusLogs as $log)
            <tr>
                <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                <td>{{ $statuses[$log->status] ?? $log->status }}</td>
                <td>{{ $log->comment }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
