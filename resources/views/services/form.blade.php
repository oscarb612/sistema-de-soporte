<div class="form-grid">
    <label>Empresa
        <select name="company_id" required>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $service->company_id ?: ($companies->count() === 1 ? $companies->first()->id : 0)) === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </label>
    <label>Nombre del servicio
        <input name="name" value="{{ old('name', $service->name) }}" required>
    </label>
    <label>Precio neto
        <input type="number" name="price" min="0" step="1" value="{{ old('price', $service->price) }}" required>
    </label>
    <label>Duración estimada (horas)
        <input type="number" name="estimated_hours" min="0.01" step="0.01" value="{{ old('estimated_hours', $service->estimated_hours) }}" placeholder="Ej: 1.5">
    </label>
    <label class="full">Descripción
        <textarea name="description">{{ old('description', $service->description) }}</textarea>
    </label>
    <label class="full" style="display:flex; align-items:center; gap:10px;">
        <input type="checkbox" name="is_active" value="1" style="width:auto;" @checked(old('is_active', $service->exists ? $service->is_active : true))>
        Servicio activo
    </label>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Guardar</button>
    <a class="btn" href="{{ route('services.index') }}">Volver</a>
</div>
