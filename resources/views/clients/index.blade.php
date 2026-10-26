@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Clientes</h1>
        <p class="muted">Registro de clientes por empresa.</p>
    </div>
    <a class="btn primary" href="{{ route('clients.create') }}">Nuevo cliente</a>
</div>

<form class="filters client-search" method="get" autocomplete="off">
    <label>Buscar
        <span class="suggest-box">
            <input
                id="client-search-input"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Nombre o RUT"
                aria-autocomplete="list"
                aria-expanded="false"
            >
            <span id="client-search-suggestions" class="suggest-list" hidden></span>
        </span>
    </label>
    <button class="btn" type="submit">Buscar</button>
    @if ($search !== '')
        <a class="btn" href="{{ route('clients.index') }}">Limpiar</a>
    @endif
</form>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>RUT</th>
        <th>Contacto</th>
        <th>Dirección</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @forelse ($clients as $client)
        <tr>
            <td>{{ $clients->firstItem() + $loop->index }}</td>
            <td>{{ $client->name }}</td>
            <td>{{ $client->formatted_rut }}</td>
            <td>{{ $client->phone }}<br><span class="muted">{{ $client->email }}</span></td>
            <td>{{ $client->address }}</td>
            <td><a class="btn" href="{{ route('clients.edit', $client) }}">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="6">No hay clientes registrados.</td></tr>
    @endforelse
    </tbody>
</table>

{{ $clients->links() }}

<style>
    .client-search label { min-width:min(420px, 100%); }
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
</style>

<script>
    (() => {
        const input = document.getElementById('client-search-input');
        const list = document.getElementById('client-search-suggestions');
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
                const response = await fetch(`{{ route('clients.search-suggestions') }}?${params.toString()}`, {
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
