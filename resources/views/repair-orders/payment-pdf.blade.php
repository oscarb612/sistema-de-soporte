<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
@page{size:A4 portrait;margin:12mm}body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111827}h1{font-size:21px;margin-bottom:5px}h2{font-size:13px;background:#dbeafe;padding:7px;margin-top:18px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #9ca3af;padding:7px;text-align:left}.money{text-align:right}.total{font-size:14px;font-weight:bold}.muted{color:#4b5563}.footer{margin-top:28px;border-top:1px solid #9ca3af;padding-top:8px;font-size:9px}
</style></head><body>
<h1>Comprobante de abono</h1>
<p><strong>{{ $order->company->name }}</strong> · RUT {{ $order->company->formatted_rut ?: 'No informado' }}<br>{{ $order->company->address }}{{ $order->company->phone ? ' · '.$order->company->phone : '' }}</p>
<p><strong>Orden:</strong> {{ $order->code }}<br><strong>Comprobante:</strong> AB-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}<br><strong>Fecha del abono:</strong> {{ $payment->payment_date->format('d-m-Y') }}</p>
<h2>Cliente</h2>
<p><strong>{{ $order->client->name }}</strong> · RUT {{ $order->client->formatted_rut }}<br>{{ $order->client->email ?: 'Correo no informado' }} · {{ $order->client->phone ?: 'Teléfono no informado' }}</p>
<h2>Detalle del abono</h2>
<table><tr><th>Concepto</th><th>Medio de pago</th><th>Presupuesto</th><th class="money">Monto abonado</th></tr><tr><td>{{ $payment->payment_type_label }}</td><td>{{ $payment->payment_method_label }}</td><td>{{ $payment->repairQuote?->quote_number ?: 'Orden completa' }}</td><td class="money total">${{ number_format((float) $payment->amount, 0, ',', '.') }}</td></tr></table>
@if($payment->device)<p><strong>Equipo:</strong> {{ trim($payment->device->type.' '.$payment->device->brand.' '.$payment->device->model) }}</p>@endif
@if($payment->notes)<p><strong>Notas:</strong> {{ $payment->notes }}</p>@endif
<h2>Resumen</h2>
<table><tr><td>Total del presupuesto/servicio</td><td class="money">${{ number_format($payment->receiptTotal(), 0, ',', '.') }}</td></tr><tr><td>Total abonado a la fecha de este comprobante</td><td class="money">${{ number_format($payment->paidThroughThisPayment(), 0, ',', '.') }}</td></tr><tr><td class="total">Saldo pendiente</td><td class="money total">${{ number_format($payment->balanceAfterPayment(), 0, ',', '.') }}</td></tr></table>
<p class="muted">Registrado por: {{ $payment->creator?->name ?: 'Sistema' }}</p>
<p class="footer">Este documento acredita el abono registrado para la orden {{ $order->code }}.</p>
</body></html>
