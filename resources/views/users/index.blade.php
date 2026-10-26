@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Usuarios</h1>
        <p class="muted">Accesos por empresa y roles operativos.</p>
    </div>
    <a class="btn primary" href="{{ route('users.create') }}">Nuevo usuario</a>
</div>

<table>
    <thead>
    <tr>
        <th>Nombre</th>
        <th>Empresa</th>
        <th>Rol</th>
        <th>Estado</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @forelse ($users as $user)
        <tr>
            <td>{{ $user->name }}<br><span class="muted">{{ $user->email }}</span></td>
            <td>{{ $user->company->name ?? 'Sistema' }}</td>
            <td>{{ str_replace('_', ' ', ucfirst($user->role)) }}</td>
            <td><span class="badge">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
                <div class="actions" style="margin:0;">
                    <a class="btn" href="{{ route('users.edit', $user) }}">Editar</a>
                    @if (! auth()->user()->is($user))
                        <form method="post" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('¿Seguro que deseas eliminar al usuario {{ addslashes($user->name) }}? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('delete')
                            <button class="btn danger" type="submit">Eliminar</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">No hay usuarios registrados.</td></tr>
    @endforelse
    </tbody>
</table>

{{ $users->links() }}
@endsection
