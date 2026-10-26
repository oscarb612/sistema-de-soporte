@extends('layouts.app')

@section('content')
<div class="login-shell">
    <section class="login-visual">
        <img src="{{ asset('images/login-engineer.webp') }}" alt="Ingeniero informático revisando un notebook">
        <div class="login-visual-overlay"></div>
        <div class="login-copy">
            <span class="login-kicker">Gestión de servicio técnico</span>
            <h1>Control completo de cada equipo, desde la recepción hasta la entrega.</h1>
            <p>Registra diagnósticos, evidencia fotográfica, presupuestos y pagos en un solo lugar.</p>
        </div>
    </section>
    <section class="login-panel">
        <div class="login-form-wrap">
            <span class="login-icon" aria-hidden="true">ST</span>
            <h2>Bienvenido</h2>
            <p class="muted">Ingresa a tu espacio de trabajo para administrar órdenes y clientes.</p>
            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <label>Correo electrónico
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nombre@empresa.cl">
                    </label>
                    <label>Contraseña
                        <input type="password" name="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña">
                    </label>
                    <label class="remember-row"><input type="checkbox" name="remember" value="1">Mantener mi sesión iniciada</label>
                </div>
                <button class="btn primary login-submit" type="submit">Ingresar al sistema</button>
            </form>
            <p class="login-security">Acceso protegido para personal autorizado.</p>
        </div>
    </section>
</div>
<style>
    body:has(.login-shell) main{padding:18px 0}
    .login-shell{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(360px,.65fr);height:calc(100dvh - 101px);min-height:540px;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:0 24px 60px rgba(15,50,75,.15)}
    .login-visual{position:relative;min-height:0;overflow:hidden;color:#fff}.login-visual img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.login-visual-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(4,40,58,.92) 0%,rgba(4,40,58,.55) 48%,rgba(4,40,58,.08) 100%)}
    .login-copy{position:absolute;left:48px;bottom:52px;width:min(540px,calc(100% - 96px))}.login-kicker{display:inline-flex;padding:7px 11px;border:1px solid rgba(255,255,255,.35);border-radius:999px;background:rgba(255,255,255,.12);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.login-copy h1{margin:18px 0 14px;color:#fff;font-size:38px;line-height:1.12}.login-copy p{margin:0;color:#dbeafe;font-size:17px;line-height:1.55}
    .login-panel{display:flex;align-items:center;padding:48px}.login-form-wrap{width:100%;max-width:390px;margin:auto}.login-icon{display:inline-flex;align-items:center;justify-content:center;width:50px;height:50px;border-radius:14px;background:var(--brand);color:#fff;font-size:18px;font-weight:800;letter-spacing:.04em}.login-form-wrap h2{margin:22px 0 7px;font-size:30px}.login-form-wrap>.muted{margin:0 0 28px;line-height:1.5}.login-form-wrap label{margin-bottom:2px}.login-form-wrap input:not([type="checkbox"]){min-height:48px}.remember-row{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:600}.remember-row input{width:auto;min-height:auto}.login-submit{width:100%;min-height:48px;margin-top:22px}.login-security{margin:22px 0 0;color:var(--muted);font-size:12px;text-align:center}
    @media(max-width:900px){body:has(.login-shell) main{padding:12px 0}.login-shell{grid-template-columns:1fr;height:auto;min-height:calc(100dvh - 89px)}.login-visual{min-height:300px}.login-copy{left:28px;bottom:28px;width:calc(100% - 56px)}.login-copy h1{font-size:28px}.login-panel{padding:30px 24px}}
    @media(max-width:560px){.login-visual{min-height:260px}.login-copy h1{font-size:23px}.login-copy p{display:none}}
</style>
@endsection
