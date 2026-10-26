<p>Hola {{ $order->client->name }},</p>
<p>Adjuntamos el comprobante de recepción de la orden <strong>{{ $order->code }}</strong> y las fotografías que documentan el estado del equipo al momento de su ingreso.</p>
<p><strong>Empresa:</strong> {{ $order->company->name }}<br><strong>Fecha:</strong> {{ $order->created_at->format('d-m-Y H:i') }}</p>
<p>Conserva este correo como respaldo.</p>
