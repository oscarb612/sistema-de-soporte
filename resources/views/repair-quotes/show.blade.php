@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $quote->quote_type === 'closure' ? 'Liquidación de cierre' : 'Presupuesto' }} {{ $quote->quote_number }}</h1>
        <p><strong>{{ $quote->quote_type === 'closure' ? 'Liquidación de cierre' : 'Presupuesto de reparación' }}</strong></p>
        <p class="muted">Orden {{ $quote->repairOrder->code }} · {{ $quote->repairOrder->client->name }}</p>
    </div>
    <div class="actions" style="margin:0;">
        <a class="btn" href="{{ route('repair-orders.show', $quote->repairOrder) }}">Ver orden</a>
        <a class="btn warning" href="{{ route('repair-quotes.print', $quote) }}" target="_blank">{{ $quote->quote_type === 'closure' ? 'Imprimir liquidación' : 'Imprimir presupuesto' }}</a>
        <form method="post" action="{{ route('repair-quotes.destroy', $quote) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este presupuesto? La orden volverá al estado Diagnosticado.');">
            @csrf
            @method('delete')
            <button class="btn danger" type="submit">Eliminar</button>
        </form>
    </div>
</div>

<div class="details">
    <div class="panel">
        <h2>Resumen</h2>
        <p><strong>Empresa:</strong> {{ $quote->repairOrder->company->name }}</p>
        <p><strong>Cliente:</strong> {{ $quote->repairOrder->client->name }}</p>
        <p><strong>Estado:</strong> <span class="badge">{{ $quote->status_label }}</span></p>
        <p><strong>Válido hasta:</strong> {{ optional($quote->valid_until)->format('d-m-Y') ?? 'Sin fecha' }}</p>
    </div>
    <div class="panel">
        <h2>Diagnóstico</h2>
        <p>{{ $quote->diagnosis_summary }}</p>
    </div>
</div>

<div class="panel" style="margin-top:16px;">
    <h2>Items</h2>
    <table>
        <thead>
        <tr>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Unitario neto</th>
            <th>Subtotal neto</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($quote->items as $item)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $item->type)) }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>${{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p><strong>Mano de obra / servicios:</strong> ${{ number_format($quote->labor_amount, 0, ',', '.') }}</p>
    <p><strong>Repuestos:</strong> ${{ number_format($quote->parts_amount, 0, ',', '.') }}</p>
    <p><strong>Descuento:</strong> ${{ number_format($quote->discount_amount, 0, ',', '.') }}</p>
    <p><strong>Neto:</strong> ${{ number_format($quote->net_amount, 0, ',', '.') }}</p>
    <p><strong>IVA (19%):</strong> ${{ number_format($quote->tax_amount, 0, ',', '.') }}</p>
    <h2>Total: ${{ number_format($quote->total_amount, 0, ',', '.') }}</h2>
    @if($quote->quote_type !== 'closure')<p class="muted">Diagnóstico de ${{number_format($quote->company->diagnostic_fee_gross ?? 20000,0,',','.')}} bonificado al aprobar. Si se rechaza, se generará el cobro de diagnóstico más el retiro realizado.</p>@endif
</div>

@if ($quote->status === 'emitido')
    <div class="details" style="margin-top:16px;">
        <div class="panel">
            <h2>Aprobar presupuesto</h2>
            <form method="post" action="{{ route('repair-quotes.approve', $quote) }}">
                @csrf
                <label>Notas de aprobación
                    <textarea name="customer_response_notes"></textarea>
                </label>
                <div class="actions">
                    <button class="btn primary" type="submit">Aprobar</button>
                </div>
            </form>
        </div>
        <div class="panel">
            <h2>Rechazar presupuesto</h2>
            <form method="post" action="{{ route('repair-quotes.reject', $quote) }}">
                @csrf
                <label>Motivo de rechazo
                    <textarea name="customer_response_notes"></textarea>
                </label>
                @if($quote->repairOrder->homeServicePreorder)<label style="display:flex;align-items:center;gap:8px;margin-top:12px"><input type="checkbox" name="customer_pickup" value="1" style="width:auto;min-height:auto"> El cliente retirará el equipo en oficina (no cobrar entrega a domicilio)</label>@endif
                <div class="actions">
                    <button class="btn" type="submit">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
