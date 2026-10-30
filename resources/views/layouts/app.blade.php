<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Ingreso a reparación' }}</title>
    <style>
        :root { --ink:#1f2933; --muted:#65758b; --line:#d8dee8; --bg:#DBEAFE; --panel:#ffffff; --brand:#116466; --accent:#d9b44a; --danger:#b42318; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial, sans-serif; color:var(--ink); background:var(--bg); }
        a { color:inherit; text-decoration:none; }
        header { background:#fff; border-bottom:1px solid var(--line); }
        .wrap { width:min(1180px, calc(100% - 32px)); margin:0 auto; }
        .topbar { min-height:64px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .brand-group { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .brand { font-weight:700; font-size:20px; color:var(--brand); }
        .area-switch { display:flex; align-items:center; padding:3px; gap:3px; border:1px solid var(--line); border-radius:9px; background:#eef3f7; }
        .area-link { padding:8px 11px; border-radius:6px; font-weight:700; color:#53657a; white-space:nowrap; }
        .area-link.active { color:#fff; background:var(--brand); }
        .area-link.recovery.active { background:#174f75; }
        nav { display:flex; gap:8px; flex-wrap:wrap; }
        nav a, .btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:38px; padding:0 12px; border:1px solid var(--line); border-radius:6px; background:#fff; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; }
        .menu-icon { width:17px; height:17px; flex:0 0 17px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
        nav a:hover, .btn:hover { border-color:var(--brand); color:var(--brand); }
        .btn.disabled { background:#eef3f7; color:var(--muted); cursor:not-allowed; pointer-events:none; }
        .menu-dropdown { position:relative; }
        .menu-dropdown summary { list-style:none; display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:38px; padding:0 12px; border:1px solid var(--line); border-radius:6px; background:#fff; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; }
        .menu-dropdown summary::-webkit-details-marker { display:none; }
        .menu-dropdown summary:hover { border-color:var(--brand); color:var(--brand); }
        .menu-dropdown[open] summary { border-color:var(--brand); color:var(--brand); }
        .menu-caret { font-size:12px; line-height:1; transition:transform .16s ease; }
        .menu-dropdown[open] .menu-caret { transform:rotate(180deg); }
        .menu-dropdown-content { position:absolute; right:0; top:44px; z-index:20; min-width:190px; padding:6px; border:1px solid var(--line); border-radius:8px; background:#fff; box-shadow:0 12px 28px rgba(31, 41, 51, .14); }
        .menu-dropdown-content a { display:flex; justify-content:flex-start; width:100%; border:0; border-radius:6px; }
        .menu-dropdown-content a:hover { background:#eef3f7; }
        .btn.primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .btn.warning { background:var(--accent); border-color:var(--accent); color:#221a00; }
        .btn.danger { background:var(--danger); border-color:var(--danger); color:#fff; }
        main { padding:28px 0 44px; }
        .page-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:20px; }
        h1 { margin:0; font-size:28px; line-height:1.2; }
        h2 { margin:0 0 14px; font-size:20px; }
        .muted { color:var(--muted); }
        .grid { display:grid; gap:16px; }
        .stats { grid-template-columns:repeat(4, minmax(0, 1fr)); }
        .stat, .panel, .card { background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:18px; }
        .stat { position:relative; min-height:112px; padding-right:76px; overflow:hidden; }
        .stat-icon { position:absolute; top:18px; right:18px; display:flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:12px; background:#e7f3f1; color:var(--brand); }
        .stat-icon svg { width:23px; height:23px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .stat strong { display:block; font-size:30px; margin-top:8px; }
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--line); border-radius:8px; overflow:hidden; }
        th, td { text-align:left; padding:12px; border-bottom:1px solid var(--line); vertical-align:top; }
        th { background:#eef3f7; font-size:13px; text-transform:uppercase; color:#405064; }
        tr:last-child td { border-bottom:0; }
        form { margin:0; }
        .form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        label { display:block; font-weight:700; margin-bottom:6px; }
        input, select, textarea { width:100%; min-height:40px; border:1px solid var(--line); border-radius:6px; padding:9px 10px; font:inherit; background:#fff; }
        textarea { min-height:96px; resize:vertical; }
        .full { grid-column:1 / -1; }
        .actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:18px; }
        .alert { border-radius:6px; padding:12px 14px; margin-bottom:16px; }
        .alert.success { background:#e8f5ee; border:1px solid #aed9c2; color:#135c37; }
        .alert.error { background:#fff0f0; border:1px solid #ffc3c0; color:var(--danger); }
        .badge { display:inline-flex; align-items:center; min-height:26px; padding:0 9px; border-radius:999px; background:#eef3f7; font-size:13px; font-weight:700; color:#405064; }
        .field-error { display:block; min-height:20px; margin-top:6px; color:var(--danger); font-size:13px; font-weight:700; }
        .user-pill { display:inline-flex; flex-direction:column; justify-content:center; min-height:38px; padding:0 12px; border-left:1px solid var(--line); color:var(--ink); font-weight:700; }
        .user-pill small { color:var(--muted); font-weight:600; margin-top:2px; }
        .filters { display:flex; gap:10px; align-items:end; flex-wrap:wrap; margin-bottom:16px; }
        .filters label { min-width:220px; }
        .details { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media (max-width: 760px) {
            .topbar, .page-head { align-items:stretch; flex-direction:column; }
            .stats, .form-grid, .details { grid-template-columns:1fr; }
            .full { grid-column:auto; }
            table { display:block; overflow-x:auto; }
        }
    </style>
</head>
<body>
@php
    $isRecoveryArea = request()->routeIs('data-recovery.*', 'data-recovery-quotes.*')
        || request('area') === 'recuperadata'
        || (isset($preorder) && $preorder->service_area === 'recuperadata');
    $homeServiceArea = $isRecoveryArea ? 'recuperadata' : 'servicio_tecnico';
@endphp
<header>
    <div class="wrap topbar">
        <div class="brand-group">
            <div class="area-switch" aria-label="Cambiar área">
                <a class="area-link {{ $isRecoveryArea ? '' : 'active' }}" href="{{ route('dashboard') }}">Servicio Técnico</a>
                <a class="area-link recovery {{ $isRecoveryArea ? 'active' : '' }}" href="{{ route('data-recovery.index') }}">Recuperadata</a>
            </div>
            @auth
                <span class="user-pill">
                    Bienvenido {{ auth()->user()->name }}
                    @if (auth()->user()->company)
                        <small>{{ auth()->user()->company->name }}</small>
                    @endif
                </span>
            @endauth
        </div>
        @auth
            <nav>
                <a href="{{ $isRecoveryArea ? route('data-recovery.dashboard') : route('dashboard') }}">Panel<svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></a>
                @if($isRecoveryArea)
                    <a href="{{ route('data-recovery.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7.5h16v12H4z"/><path d="M8 7.5V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2.5M4 12h16"/></svg>Casos</a>
                @else
                    <a href="{{ route('repair-orders.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M7 3h10v4H7z"/><path d="M5 5h14v16H5zM8 11h8M8 15h8"/></svg>Órdenes</a>
                @endif
                <a href="{{ route('home-service.index', ['area' => $homeServiceArea]) }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>Retiros</a>
                <details class="menu-dropdown">
                    <summary><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.12-1.28l2-1.55-2-3.46-2.47 1A7 7 0 0 0 14.2 5.4L13.86 3h-4l-.34 2.4a7 7 0 0 0-2.2 1.3l-2.48-1-2 3.46 2 1.55A7 7 0 0 0 4.72 12a7 7 0 0 0 .12 1.28l-2 1.55 2 3.46 2.47-1a7 7 0 0 0 2.21 1.3l.34 2.4h4l.34-2.4a7 7 0 0 0 2.2-1.3l2.48 1 2-3.46-2-1.55A7 7 0 0 0 19 12z"/></svg>Configuración <span class="menu-caret" aria-hidden="true">&#9662;</span></summary>
                    <div class="menu-dropdown-content">
                        <a href="{{ route('companies.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 21V7l8-4 8 4v14M8 10h2M14 10h2M8 14h2M14 14h2M10 21v-4h4v4"/></svg>Empresas</a>
                        <a href="{{ route('clients.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3 20a6 6 0 0 1 12 0M14 15.5a5 5 0 0 1 7 4.5"/></svg>Clientes</a>
                        <a href="{{ route('services.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-2.4 2.4-3-3z"/></svg>Servicios</a>
                        <a href="{{ route('mail-settings.edit') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>Correo saliente</a>
                        <a href="{{ route('ai-settings.edit') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="5" y="7" width="14" height="12" rx="3"/><path d="M12 3v4M9 12h.01M15 12h.01M9 16h6"/></svg>Inteligencia artificial</a>
                        <a href="{{ route('transport-settings.edit') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>Retiros a domicilio</a>
                        <a href="{{ route('pwa-settings.show') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M10 5h4M11 19h2"/></svg>Aplicación móvil</a>
                        <a href="{{ route('users.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Usuarios</a>
                        <a href="{{ route('activity-logs.index') }}"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 9h8M8 13h8M8 17h5"/></svg>Logs</a>
                    </div>
                </details>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit"><svg class="menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h10"/></svg>Salir</button>
                </form>
            </nav>
        @endauth
    </div>
</header>
<main>
    <div class="wrap">
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error">
                <strong>Revisa los datos ingresados.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</main>
<script>
    document.addEventListener('click', (event) => {
        document.querySelectorAll('.menu-dropdown[open]').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                dropdown.removeAttribute('open');
            }
        });
    });
</script>
</body>
</html>
