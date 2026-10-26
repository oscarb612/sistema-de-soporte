<div class="form-grid">
    <label>Nombre
        <input name="name" value="{{ old('name', $company->name ?? '') }}" required>
    </label>
    <label>RUT
        <input name="rut" value="{{ old('rut', isset($company) ? $company->formatted_rut : '') }}">
    </label>
    <label class="full">Dirección
        <input name="address" value="{{ old('address', $company->address ?? '') }}">
    </label>
    <label>Teléfono
        <input name="phone" value="{{ old('phone', $company->phone ?? '') }}">
    </label>
    <label>Correo
        <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}">
    </label>
    <label>Diagnóstico (precio final con IVA)
        <input type="number" min="0" step="1" name="diagnostic_fee_gross" value="{{ old('diagnostic_fee_gross', $company->diagnostic_fee_gross ?? 20000) }}" required>
    </label>
    <label>Plazo Servicio Técnico (horas hábiles)
        <input type="number" min="1" name="technical_diagnostic_hours" value="{{ old('technical_diagnostic_hours', $company->technical_diagnostic_hours ?? 24) }}" required>
    </label>
    <label>Plazo Recuperadata (horas hábiles)
        <input type="number" min="1" name="recovery_diagnostic_hours" value="{{ old('recovery_diagnostic_hours', $company->recovery_diagnostic_hours ?? 48) }}" required>
    </label>
    <label>Plan
        <select name="plan">
            @foreach (['starter' => 'Starter', 'pro' => 'Pro', 'empresa' => 'Empresa'] as $value => $label)
                <option value="{{ $value }}" @selected(old('plan', $company->plan ?? 'starter') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>Activa
        <select name="is_active">
            <option value="1" @selected(old('is_active', $company->is_active ?? true))>Si</option>
            <option value="0" @selected(! old('is_active', $company->is_active ?? true))>No</option>
        </select>
    </label>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Guardar</button>
    <a class="btn" href="{{ route('companies.index') }}">Volver</a>
</div>
