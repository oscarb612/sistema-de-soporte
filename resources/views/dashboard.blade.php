@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Panel de reparaciones</h1>
        <p class="muted">Resumen general de empresas, clientes y órdenes de trabajo.</p>
    </div>
    <a class="btn primary" href="{{ route('repair-orders.create') }}">Nueva orden</a>
</div>

<div class="grid stats">
    <div class="stat"><span>Empresas</span><strong>{{ $companiesCount }}</strong></div>
    <div class="stat"><span>Clientes</span><strong>{{ $clientsCount }}</strong></div>
    <div class="stat"><span>Órdenes</span><strong>{{ $ordersCount }}</strong></div>
    <div class="stat"><span>Pendientes</span><strong>{{ $pendingOrdersCount }}</strong></div>
</div>

<div class="panel" style="margin-top:18px;">
    <h2>Últimas órdenes</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Código</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Equipo</th>
            <th>Estado</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($recentOrders as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->code }}</td>
                <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                <td>{{ $order->client->name }}</td>
                <td>{{ $order->device->type }} {{ $order->device->brand }}</td>
                <td><span class="badge">{{ $order->status_label }}</span></td>
                <td><a class="btn" href="{{ route('repair-orders.show', $order) }}">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="7">Aún no hay órdenes registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
