<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#174f75">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Retiros">
    <link rel="manifest" href="{{ asset('home-service-manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('images/home-service-icon.svg') }}">
    <title>{{ $title ?? 'Conexionweb Retiros' }}</title>
    <style>
        :root{--brand:#174f75;--brand2:#116466;--bg:#dbeafe;--panel:#fff;--ink:#172033;--muted:#64748b;--line:#d8e1eb;--danger:#b42318;--safe-bottom:env(safe-area-inset-bottom,0px)}
        *{box-sizing:border-box}[hidden]{display:none!important}html,body{margin:0;min-height:100%;font-family:Arial,sans-serif;color:var(--ink);background:var(--bg)}body{padding-bottom:calc(72px + var(--safe-bottom))}a{text-decoration:none;color:inherit}
        .appbar{position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;min-height:64px;padding:calc(8px + env(safe-area-inset-top,0px)) 16px 8px;background:var(--brand);color:#fff;box-shadow:0 2px 12px #0f294033}.app-brand{display:flex;align-items:center;gap:10px;font-weight:800}.app-brand img{width:38px;height:38px;border-radius:10px}.app-brand small{display:block;font-size:11px;font-weight:500;opacity:.8}.user{font-size:12px;text-align:right;max-width:120px}
        main{width:min(760px,100%);margin:auto;padding:16px}.panel,.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px;box-shadow:0 4px 16px #174f7512}.spaced{margin-top:14px}h1{font-size:24px;margin:0}h2{font-size:19px;margin:0 0 14px}.muted{color:var(--muted)}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:#fff;font:inherit;font-weight:700;cursor:pointer}.btn.primary{background:var(--brand2);border-color:var(--brand2);color:#fff}.btn.danger{background:var(--danger);border-color:var(--danger);color:#fff}.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:14px}
        .form-grid{display:grid;grid-template-columns:1fr;gap:13px}label{display:block;font-weight:700;margin-bottom:5px}input,select,textarea{width:100%;min-height:46px;padding:10px;border:1px solid #b9c7d6;border-radius:9px;background:#fff;font:inherit}textarea{min-height:96px}.full{grid-column:1/-1}.alert{padding:12px;border-radius:10px;margin-bottom:12px}.alert.success{background:#e8f5ee;color:#135c37}.alert.error{background:#fff0f0;color:var(--danger)}.badge{display:inline-flex;padding:5px 9px;border-radius:99px;background:#e8f0f6;color:#405064;font-size:12px;font-weight:700}
        .bottom-nav{position:fixed;z-index:40;left:0;right:0;bottom:0;display:grid;grid-template-columns:repeat(3,1fr);padding:6px 8px calc(6px + var(--safe-bottom));background:#fff;border-top:1px solid var(--line);box-shadow:0 -5px 18px #17203318}.bottom-nav a,.bottom-nav button{width:100%;border:0;background:none;min-height:54px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;font:inherit;font-size:11px;font-weight:700;color:var(--muted)}.bottom-nav .active{color:var(--brand)}.bottom-nav .icon{font-size:22px;line-height:1}.bottom-nav form{margin:0}
        @media(min-width:620px){.form-grid{grid-template-columns:repeat(2,1fr)}}
    </style>
</head>
<body>
<header class="appbar"><a class="app-brand" href="{{ route('home-service.mobile-app') }}"><img src="{{ asset('images/home-service-icon.svg') }}" alt=""><span>Conexionweb Retiros<small>Aplicación de técnicos</small></span></a><span class="user">{{ auth()->user()->name }}</span></header>
<main>
    @if(session('success'))<div class="alert success">{{session('success')}}</div>@endif
    @if($errors->any())<div class="alert error"><b>Revisa los datos ingresados.</b><ul>@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<nav class="bottom-nav" aria-label="Navegación de la aplicación">
    <a class="{{request()->routeIs('home-service.mobile-app')?'active':''}}" href="{{route('home-service.mobile-app')}}"><span class="icon">⌂</span>Retiros</a>
    <button type="button" onclick="location.reload()"><span class="icon">↻</span>Actualizar</button>
    <form method="post" action="{{route('logout')}}">@csrf<button type="submit"><span class="icon">⇥</span>Salir</button></form>
</nav>
<script>if('serviceWorker'in navigator){addEventListener('load',()=>navigator.serviceWorker.register('{{asset('home-service-sw.js')}}',{scope:'/home-service/'}))}</script>
@stack('scripts')
</body>
</html>
