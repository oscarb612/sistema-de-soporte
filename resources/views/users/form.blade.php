<div class="form-grid">
    <label>Nombre
        <input name="name" value="{{ old('name', $managedUser->name ?? '') }}" required>
    </label>
    <label>Correo
        <input type="email" name="email" value="{{ old('email', $managedUser->email ?? '') }}" required>
    </label>
    <label>Empresa
        <select name="company_id">
            <option value="">Sin empresa</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $managedUser->company_id ?? 0) === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </label>
    <label>Rol
        <select name="role" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $managedUser->role ?? 'recepcionista') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>Contrasena
        <input type="password" name="password" @if (! isset($managedUser)) required @endif>
    </label>
    <label>Activo
        <select name="is_active">
            <option value="1" @selected(old('is_active', $managedUser->is_active ?? true))>Si</option>
            <option value="0" @selected(! old('is_active', $managedUser->is_active ?? true))>No</option>
        </select>
    </label>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Guardar</button>
    <a class="btn" href="{{ route('users.index') }}">Volver</a>
</div>
