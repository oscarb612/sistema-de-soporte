@extends('layouts.app')
@section('content')
<div class="page-head"><div><h1>Casos de recuperación de datos</h1><p class="muted">Recepción, diagnóstico y cadena de custodia de unidades.</p></div><a class="btn primary" href="{{ route('data-recovery.create') }}">Nuevo caso</a></div>
<form class="filters" method="get"><label>Buscar<input name="q" value="{{ request('q') }}" placeholder="Código, nombre o RUT"></label><label>Estado<select name="status"><option value="">Todos</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></label><button class="btn">Buscar</button></form>
<div class="table-scroll"><table><thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Unidad</th><th>Serie</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($cases as $case)<tr><td><strong>{{ $case->code }}</strong></td><td>{{ $case->received_at?->format('d-m-Y H:i') }}</td><td>{{ $case->client->name }}<br><span class="muted">{{ $case->client->formatted_rut }}</span></td><td>{{ $case->unit_type }}<br><span class="muted">{{ $case->brand }} {{ $case->capacity }}</span></td><td>{{ $case->serial_number ?: '—' }}</td><td><span class="badge">{{ $case->status_label }}</span></td><td><div class="row-actions"><a class="btn" href="{{ route('data-recovery.show',$case) }}">Ver</a><a class="btn" href="{{ route('data-recovery.edit',$case) }}">Editar</a><form method="post" action="{{ route('data-recovery.destroy',$case) }}" onsubmit="return confirm('¿Eliminar definitivamente este caso?')">@csrf @method('delete')<button class="btn danger" title="Eliminar">🗑</button></form></div></td></tr>
@empty<tr><td colspan="7">No hay casos registrados.</td></tr>@endforelse
</tbody></table></div>{{ $cases->links() }}
<style>.table-scroll{overflow-x:auto}.row-actions{display:flex;gap:6px;white-space:nowrap}.row-actions .btn{min-height:34px;padding:0 10px;font-size:13px}</style>
@endsection
