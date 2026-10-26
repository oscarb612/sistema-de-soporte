@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Inteligencia artificial</h1>
        <p class="muted">Configura Groq para reconocer marca, modelo y número de serie desde fotografías.</p>
    </div>
</div>

@if ($company)
    @if (auth()->user()->role === 'super_admin' && $companies->count() > 1)
        <form class="filters" method="get" action="{{ route('ai-settings.edit') }}">
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
        <form method="post" action="{{ route('ai-settings.update') }}">
            @csrf
            @method('put')
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            <div class="form-grid">
                <label>Modelo de visión
                    <select name="groq_model" required>
                        <option value="qwen/qwen3.8-27b" @selected(old('groq_model', $company->groq_model) === 'qwen/qwen3.8-27b')>Qwen 3.8 27B</option>
                    </select>
                </label>
                <label>Clave API de Groq
                    <input type="password" name="groq_api_key" autocomplete="new-password" placeholder="{{ $company->groq_api_key ? 'Configurada · ingresa otra para reemplazarla' : 'gsk_...' }}">
                    <span class="muted">{{ $company->groq_api_key ? 'Clave configurada y almacenada cifrada.' : 'Aún no hay una clave configurada.' }}</span>
                </label>
            </div>
            <p class="muted">Por seguridad, la clave guardada nunca se vuelve a mostrar. Deja el campo vacío para conservarla.</p>
            <div class="actions"><button class="btn primary" type="submit">Guardar configuración</button></div>
        </form>
    </div>
@else
    <div class="alert error">No hay una empresa disponible para configurar.</div>
@endif
@endsection
