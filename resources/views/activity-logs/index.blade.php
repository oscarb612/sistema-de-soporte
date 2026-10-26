@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Logs de actividad</h1>
        <p class="muted">Registros generados desde {{ $from->format('d-m-Y') }} hasta hoy.</p>
    </div>
</div>

<form class="filters" method="get">
    <label>Empresa
        <select name="company_id">
            <option value="">Todas</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) request('company_id') === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </label>
    <label>Acción
        <select name="action">
            <option value="">Todas</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn" type="submit">Filtrar</button>
</form>

<table>
    <thead>
    <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Empresa</th>
        <th>Acción</th>
        <th>Detalle</th>
        <th>Origen</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
            <td>{{ $log->user->name ?? 'Sistema' }}<br><span class="muted">{{ $log->user->email ?? '' }}</span></td>
            <td>{{ $log->company->name ?? 'Sistema' }}</td>
            <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span></td>
            <td>
                {{ $log->description }}
                @if ($log->subject_type)
                    <br><span class="muted">{{ $log->subject_type }} #{{ $log->subject_id }}</span>
                @endif
            </td>
            <td>{{ $log->ip_address }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No hay registros de actividad en los ultimos 60 dias.</td></tr>
    @endforelse
    </tbody>
</table>

{{ $logs->links() }}
@endsection
