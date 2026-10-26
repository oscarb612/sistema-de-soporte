<div class="form-grid">
    <label>Empresa
        <select name="company_id" required>
            <option value="">Seleccionar</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $client->company_id ?? 0) === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </label>
    <label>RUT
        <input id="rut" name="rut" value="{{ old('rut', isset($client) ? $client->formatted_rut : '') }}" required autocomplete="off">
        <span id="rut_error" class="field-error"></span>
    </label>
    <label>Nombre o Razon social
        <input name="name" value="{{ old('name', $client->name ?? '') }}" required data-rut-locked>
    </label>
    <label>Teléfono
        <input name="phone" value="{{ old('phone', $client->phone ?? '') }}" data-rut-locked>
    </label>
    <label>Correo
        <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" data-rut-locked>
    </label>
    <label>Dirección
        <input name="address" value="{{ old('address', $client->address ?? '') }}" data-rut-locked>
    </label>
    <x-chile-location :region="$client->region ?? ''" :commune="$client->commune ?? ''" :locked="true" />
    <label class="full">Notas
        <textarea name="notes" data-rut-locked>{{ old('notes', $client->notes ?? '') }}</textarea>
    </label>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Guardar</button>
    <a class="btn" href="{{ route('clients.index') }}">Volver</a>
</div>

<script>
    (() => {
        const rut = document.getElementById('rut');
        const rutError = document.getElementById('rut_error');
        const lockedFields = document.querySelectorAll('[data-rut-locked]');

        if (!rut) {
            return;
        }

        const formatRut = (value) => {
            const cleanRut = value.replace(/[^0-9kK]/g, '').toUpperCase();

            if (cleanRut.length <= 1) {
                return cleanRut;
            }

            const body = cleanRut.slice(0, -1);
            const digit = cleanRut.slice(-1);
            const formattedBody = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            return `${formattedBody}-${digit}`;
        };

        const isValidRut = (value) => {
            const cleanRut = value.replace(/[^0-9kK]/g, '').toUpperCase();

            if (cleanRut.length < 2) {
                return false;
            }

            const body = cleanRut.slice(0, -1);
            const digit = cleanRut.slice(-1);

            if (!/^\d+$/.test(body)) {
                return false;
            }

            let sum = 0;
            let multiplier = 2;

            for (let index = body.length - 1; index >= 0; index--) {
                sum += Number(body[index]) * multiplier;
                multiplier = multiplier === 7 ? 2 : multiplier + 1;
            }

            const expected = 11 - (sum % 11);
            const expectedDigit = expected === 11 ? '0' : expected === 10 ? 'K' : String(expected);

            return digit === expectedDigit;
        };

        const validateRutField = () => {
            if (!rut.value.trim()) {
                rut.setCustomValidity('');
                rutError.textContent = '';
                return false;
            }

            const valid = isValidRut(rut.value);
            rut.setCustomValidity(valid ? '' : 'Ingresa un RUT válido.');
            rutError.textContent = valid ? '' : 'RUT inválido. Revisa el número y dígito verificador.';

            return valid;
        };

        const refreshFieldLock = () => {
            const valid = validateRutField();

            lockedFields.forEach((field) => {
                field.disabled = !valid;
            });
        };

        rut.addEventListener('input', () => {
            rut.value = formatRut(rut.value);
            refreshFieldLock();
        });
        rut.addEventListener('blur', refreshFieldLock);
        rut.value = formatRut(rut.value);
        refreshFieldLock();
    })();
</script>
