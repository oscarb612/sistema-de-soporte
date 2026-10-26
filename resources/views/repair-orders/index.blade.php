@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Órdenes de reparación</h1>
        <p class="muted">Ingreso, diagnóstico y seguimiento de equipos.</p>
    </div>
    <a class="btn primary" href="{{ route('repair-orders.create') }}">Nueva orden</a>
</div>

<form class="filters order-search" method="get" autocomplete="off">
    <label>Buscar
        <span class="suggest-box">
            <input
                id="order-search-input"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Nombre o RUT"
                aria-autocomplete="list"
                aria-expanded="false"
            >
            <span id="order-search-suggestions" class="suggest-list" hidden></span>
        </span>
    </label>
    <button class="btn" type="submit">Buscar</button>
    @if ($search !== '')
        <a class="btn" href="{{ route('repair-orders.index') }}">Limpiar</a>
    @endif
</form>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>RUT</th>
        <th>Equipo</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @forelse ($orders as $order)
        <tr>
            <td>{{ $orders->firstItem() + $loop->index }}</td>
            <td>{{ $order->code }}</td>
            <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
            <td>{{ $order->client->name }}<br><span class="muted">{{ $order->client->phone }}</span></td>
            <td>{{ $order->client->formatted_rut }}</td>
            <td>{{ $order->device->type }} {{ $order->device->brand }}<br><span class="muted">{{ $order->device->model }}</span></td>
            <td><span class="badge">{{ $order->service_mode_label }}</span></td>
            <td><span class="badge">{{ $order->status_label }}</span></td>
            <td>
                <div class="order-row-actions">
                <a class="btn" href="{{ route('repair-orders.show', $order) }}">Ver</a>
                <a class="btn" href="{{ route('repair-orders.edit', $order) }}">Editar</a>
                <form method="post" action="{{ route('repair-orders.destroy', $order) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta orden? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('delete')
                    <button class="btn danger icon-btn" type="submit" aria-label="Eliminar orden" title="Eliminar orden">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                            <path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-2 6h10l-1 11H8L7 9Zm3 2v7h2v-7h-2Zm4 0v7h2v-7h-2Z"/>
                        </svg>
                    </button>
                </form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="9">No hay órdenes registradas.</td></tr>
    @endforelse
    </tbody>
</table>

{{ $orders->links() }}

<style>
    .order-search label { min-width:min(420px, 100%); }
    .suggest-box { position:relative; display:block; }
    .suggest-list {
        position:absolute;
        left:0;
        right:0;
        top:44px;
        z-index:30;
        overflow:hidden;
        border:1px solid var(--line);
        border-radius:6px;
        background:#fff;
        box-shadow:0 12px 28px rgba(31, 41, 51, .14);
    }
    .suggest-item {
        display:block;
        width:100%;
        padding:10px 12px;
        border:0;
        border-bottom:1px solid var(--line);
        background:#fff;
        color:var(--ink);
        text-align:left;
        cursor:pointer;
    }
    .suggest-item:last-child { border-bottom:0; }
    .suggest-item:hover, .suggest-item:focus { background:#eef3f7; outline:none; }
    .suggest-item strong { display:block; }
    .suggest-item span { display:block; margin-top:2px; color:var(--muted); font-size:13px; }
    .order-row-actions { display:flex; align-items:center; flex-wrap:nowrap; gap:6px; white-space:nowrap; }
    .order-row-actions .btn { min-height:34px; padding:0 10px; font-size:13px; }
    .order-row-actions .icon-btn { width:36px; padding:0; }
    .order-row-actions form { flex:0 0 auto; }
</style>

<script>
    (() => {
        const input = document.getElementById('order-search-input');
        const list = document.getElementById('order-search-suggestions');
        const form = input?.closest('form');
        let controller;

        if (!input || !list || !form) {
            return;
        }

        const hideSuggestions = () => {
            list.hidden = true;
            list.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        };

        const showSuggestions = (clients) => {
            if (!clients.length) {
                hideSuggestions();
                return;
            }

            list.innerHTML = '';

            clients.forEach((client) => {
                const item = document.createElement('button');
                const name = document.createElement('strong');
                const rut = document.createElement('span');

                item.className = 'suggest-item';
                item.type = 'button';
                item.dataset.value = client.value;
                name.textContent = client.name;
                rut.textContent = client.rut;

                item.append(name, rut);
                list.append(item);
            });

            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        input.addEventListener('input', async () => {
            const query = input.value.trim();

            if (controller) {
                controller.abort();
            }

            if (query.length < 2) {
                hideSuggestions();
                return;
            }

            controller = new AbortController();
            const params = new URLSearchParams({ q: query });

            try {
                const response = await fetch(`{{ route('repair-orders.search-suggestions') }}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    hideSuggestions();
                    return;
                }

                showSuggestions(await response.json());
            } catch (error) {
                if (error.name !== 'AbortError') {
                    hideSuggestions();
                }
            }
        });

        list.addEventListener('click', (event) => {
            const item = event.target.closest('.suggest-item');

            if (!item) {
                return;
            }

            input.value = item.dataset.value;
            hideSuggestions();
            form.submit();
        });

        document.addEventListener('click', (event) => {
            if (!form.contains(event.target)) {
                hideSuggestions();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideSuggestions();
            }
        });
    })();
</script>
@endsection
