@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Empresas</h1>
        <p class="muted">Cada empresa separa sus clientes, equipos y órdenes.</p>
    </div>
    @if (auth()->user()->role === 'super_admin')
        <a class="btn primary" href="{{ route('companies.create') }}">Nueva empresa</a>
    @endif
</div>

<table>
    <thead>
    <tr>
        <th>Nombre</th>
        <th>RUT</th>
        <th>Contacto</th>
        <th>Plan</th>
        <th>Estado</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @forelse ($companies as $company)
        <tr>
            <td>{{ $company->name }}</td>
            <td>{{ $company->formatted_rut }}</td>
            <td>{{ $company->phone }}<br><span class="muted">{{ $company->email }}</span></td>
            <td>{{ ucfirst($company->plan) }}</td>
            <td><span class="badge">{{ $company->is_active ? 'Activa' : 'Inactiva' }}</span></td>
            <td>
                <div class="actions" style="margin:0;">
                    <a class="btn" href="{{ route('companies.edit', $company) }}">Editar</a>
                    @if (auth()->user()->role === 'super_admin')
                        <form method="post" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('ADVERTENCIA: se eliminarán permanentemente la empresa {{ addslashes($company->name) }} y todos sus usuarios, clientes, equipos, órdenes, presupuestos, pagos y servicios. ¿Deseas continuar?');">
                            @csrf
                            @method('delete')
                            <button class="btn danger" type="submit">Eliminar</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="6">No hay empresas registradas.</td></tr>
    @endforelse
    </tbody>
</table>

{{ $companies->links() }}
@endsection
