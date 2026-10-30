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
    <div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21V7l8-4 8 4v14M8 10h2M14 10h2M8 14h2M14 14h2M10 21v-4h4v4"/></svg></span><span>Empresas</span><strong>{{ $companiesCount }}</strong></div>
    <div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3 20a6 6 0 0 1 12 0M14 15.5a5 5 0 0 1 7 4.5"/></svg></span><span>Clientes</span><strong>{{ $clientsCount }}</strong></div>
    <div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h10v4H7zM5 5h14v16H5zM8 11h8M8 15h8"/></svg></span><span>Órdenes</span><strong>{{ $ordersCount }}</strong></div>
    <div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><span>Pendientes</span><strong>{{ $pendingOrdersCount }}</strong></div>
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
