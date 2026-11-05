<p>Hola {{ $payment->repairOrder->client->name }},</p>
<p>Adjuntamos el <strong>Comprobante de abono</strong> correspondiente a la orden <strong>{{ $payment->repairOrder->code }}</strong>.</p>
<p><strong>Abono recibido:</strong> ${{ number_format((float) $payment->amount, 0, ',', '.') }}<br>
<strong>Total abonado:</strong> ${{ number_format($payment->paidThroughThisPayment(), 0, ',', '.') }}<br>
<strong>Saldo pendiente:</strong> ${{ number_format($payment->balanceAfterPayment(), 0, ',', '.') }}</p>
<p>Conserva este correo como respaldo.</p>
