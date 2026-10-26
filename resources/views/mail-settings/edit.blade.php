@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Correo saliente</h1>
        <p class="muted">Define el remitente que verá el cliente al recibir comprobantes y fotografías.</p>
    </div>
</div>

@if ($company)
    @if (auth()->user()->role === 'super_admin' && $companies->count() > 1)
        <form class="filters" method="get" action="{{ route('mail-settings.edit') }}">
            <label>Empresa
                <select name="company_id" onchange="this.form.submit()">
                    @foreach ($companies as $item)
                        <option value="{{ $item->id }}" @selected($company->id === $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    @endif

    <div class="panel">
        <form method="post" action="{{ route('mail-settings.update') }}">
            @csrf
            @method('put')
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            <div class="form-grid">
                <label>Nombre del remitente
                    <input name="mail_from_name" value="{{ old('mail_from_name', $company->mail_from_name ?: $company->name) }}" required placeholder="Ej: Servicio Técnico Mi Empresa">
                </label>
                <label>Correo del remitente
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $company->mail_from_address ?: $company->email) }}" required placeholder="soporte@empresa.cl">
                </label>
                <label>Servidor SMTP
                    <input name="smtp_host" value="{{ old('smtp_host', $company->smtp_host) }}" required placeholder="smtp.empresa.cl">
                </label>
                <label>Puerto SMTP
                    <input type="number" name="smtp_port" min="1" max="65535" value="{{ old('smtp_port', $company->smtp_port ?: 587) }}" required>
                </label>
                <label>Usuario SMTP
                    <input name="smtp_username" value="{{ old('smtp_username', $company->smtp_username) }}" required autocomplete="off">
                </label>
                <label>Contraseña SMTP
                    <input type="password" name="smtp_password" autocomplete="new-password" placeholder="{{ $company->smtp_password ? 'Configurada · deja vacío para conservarla' : 'Ingresa la contraseña' }}">
                    <span class="muted">{{ $company->smtp_password ? 'Contraseña configurada y almacenada cifrada.' : 'Aún no hay una contraseña configurada.' }}</span>
                </label>
                <label>Seguridad
                    <select name="smtp_security" required>
                        <option value="tls" @selected(old('smtp_security', $company->smtp_security ?: 'tls') === 'tls')>TLS / STARTTLS (recomendado)</option>
                        <option value="ssl" @selected(old('smtp_security', $company->smtp_security) === 'ssl')>SSL</option>
                        <option value="none" @selected(old('smtp_security', $company->smtp_security) === 'none')>Sin cifrado</option>
                    </select>
                </label>
            </div>
            <p class="muted">La contraseña SMTP se almacena cifrada y nunca vuelve a mostrarse.</p>
            <div class="actions">
                <button class="btn primary" type="submit">Guardar configuración</button>
            </div>
        </form>
    </div>
@else
    <div class="alert error">No hay una empresa disponible para configurar.</div>
@endif
@endsection
