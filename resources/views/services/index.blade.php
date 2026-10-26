@extends('layouts.app')

@section('content')
<div class="page-head">
    <div><h1>Catálogo de servicios</h1><p class="muted">Servicios con precio previamente definido.</p></div>
    <a class="btn primary" href="{{ route('services.create') }}">Nuevo servicio</a>
</div>
<table>
    <thead><tr><th>Servicio</th><th>Empresa</th><th>Precio neto</th><th>Duración</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($services as $service)
        <tr>
            <td><strong>{{ $service->name }}</strong><br><span class="muted">{{ $service->description }}</span></td>
            <td>{{ $service->company->name }}</td>
            <td>${{ number_format((float) $service->price, 0, ',', '.') }}</td>
            <td>{{ $service->estimated_hours ? number_format((float) $service->estimated_hours, 2, ',', '.').' h' : 'Sin definir' }}</td>
            <td><span class="badge">{{ $service->is_active ? 'Activo' : 'Inactivo' }}</span></td>
            <td><a class="btn" href="{{ route('services.edit', $service) }}">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="6">No hay servicios registrados.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $services->links() }}
@endsection
