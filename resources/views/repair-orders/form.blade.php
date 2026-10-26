@php
    $device = $order->device ?? null;
    $clientModel = $order->client ?? null;
    $selectedCompanyId = old('company_id', $order->company_id ?? ($companies->count() === 1 ? $companies->first()->id : 0));
    $devicesData = old('devices');
    $directItem = isset($order) ? $order->items->first() : null;
    $selectedMode = old('service_mode', $order->service_mode ?? 'diagnostico');

    if ($devicesData === null) {
        $loadedDevices = isset($order) ? $order->devices : collect();
        $sourceDevices = $loadedDevices->isNotEmpty() ? $loadedDevices : collect([$device])->filter();
        $devicesData = $sourceDevices->map(fn ($item) => [
            'id' => $item->id,
            'type' => $item->type,
            'brand' => $item->brand,
            'model' => $item->model,
            'serial_number' => $item->serial_number,
            'accessories' => $item->accessories,
            'physical_condition' => $item->physical_condition,
        ])->values()->all();
    }

    if (empty($devicesData)) {
        $devicesData = [[
            'id' => null,
            'type' => 'Notebook',
            'brand' => null,
            'model' => null,
            'serial_number' => null,
            'accessories' => null,
            'physical_condition' => null,
        ]];
    }
@endphp

<div class="form-grid">
    <label>Empresa
        <select id="company_id" name="company_id" required>
            <option value="">Seleccionar</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </label>
    @isset($order)
        <label>Estado
            <select name="status" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    @else
        <input type="hidden" name="status" value="ingresado">
    @endisset
</div>

<div class="panel" style="margin-top:24px;">
    <h2>Tipo de atención</h2>
    <div class="form-grid">
        <label>Flujo de la orden
            <select id="service_mode" name="service_mode" required>
                <option value="diagnostico" @selected($selectedMode === 'diagnostico')>Requiere diagnóstico</option>
                <option value="precio_definido" @selected($selectedMode === 'precio_definido')>Servicio con precio definido</option>
            </select>
        </label>
    </div>

    <div id="direct_service_fields" style="margin-top:16px;">
        <div class="form-grid">
            <label>Servicio
                <select id="service_id" name="service_id">
                    <option value="">Seleccionar servicio</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}"
                                data-company="{{ $service->company_id }}"
                                data-price="{{ $service->price }}"
                                data-description="{{ $service->description }}"
                                @selected((int) old('service_id', $directItem?->service_id) === $service->id)>
                            {{ $service->name }} - ${{ number_format((float) $service->price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <span id="service_description" class="muted" style="display:block; margin-top:6px;"></span>
            </label>
            <label>Cantidad
                <input id="service_quantity" type="number" name="service_quantity" min="1" value="{{ old('service_quantity', $directItem?->quantity ?? 1) }}">
            </label>
            <label>Precio unitario neto
                <input id="service_unit_price" type="number" name="service_unit_price" min="0" step="1" value="{{ old('service_unit_price', $directItem?->unit_price) }}">
            </label>
            <label>Descuento
                <input id="discount_amount" type="number" name="discount_amount" min="0" step="1" value="{{ old('discount_amount', $order->discount_amount ?? 0) }}">
            </label>
            <div class="full grid stats">
                <div class="stat"><span>Neto</span><strong id="direct_service_net" style="font-size:24px;">$0</strong></div>
                <div class="stat"><span>IVA (19%)</span><strong id="direct_service_tax" style="font-size:24px;">$0</strong></div>
                <div class="stat"><span>Total</span><strong id="direct_service_total" style="font-size:24px;">$0</strong></div>
            </div>
            <label class="full" style="display:flex; align-items:flex-start; gap:10px;">
                <input id="customer_acceptance" type="checkbox" name="customer_acceptance" value="1" style="width:auto; margin-top:3px;" @checked(old('customer_acceptance', isset($order) && $order->customer_accepted_at))>
                Confirmo que el cliente conoce y acepta el servicio y su valor.
            </label>
        </div>
    </div>
</div>

<h2 style="margin-top:24px;">Cliente</h2>
<input id="client_id" type="hidden" name="client_id" value="{{ old('client_id', $order->client_id ?? '') }}">
<div class="form-grid">
    <label>RUT
        <input id="client_rut" name="client_rut" value="{{ old('client_rut', $clientModel?->formatted_rut ?? '') }}" required autocomplete="off" data-rut-input>
        <span id="client_rut_error" class="field-error"></span>
    </label>
    <label>Nombre o Razon social
        <input id="client_name" name="client_name" value="{{ old('client_name', $clientModel->name ?? '') }}" required data-rut-locked>
    </label>
    <label>Teléfono
        <input id="client_phone" name="client_phone" value="{{ old('client_phone', $clientModel->phone ?? '') }}" data-rut-locked>
    </label>
    <label>Correo
        <input id="client_email" type="email" name="client_email" value="{{ old('client_email', $clientModel->email ?? '') }}" data-rut-locked>
    </label>
    <label class="full">Dirección
        <input id="client_address" name="client_address" value="{{ old('client_address', $clientModel->address ?? '') }}" data-rut-locked>
    </label>
    <x-chile-location :region="$clientModel->region ?? ''" :commune="$clientModel->commune ?? ''" :locked="true" />
    <p id="client_lookup_status" class="muted full" style="margin:0;"></p>
</div>

<div class="page-head" style="margin-top:24px; margin-bottom:12px;">
    <h2 style="margin:0;">Equipos</h2>
    <button id="add_device" class="btn primary" type="button">Agregar otro equipo</button>
</div>

<div id="devices_container" class="grid">
    @foreach ($devicesData as $index => $deviceItem)
        <div class="panel device-block" data-device-block>
            <div class="page-head" style="margin-bottom:12px;">
                <h2 style="margin:0;">Equipo {{ $index + 1 }}</h2>
                <button class="btn remove-device" type="button" @if ($loop->first) style="display:none;" @endif>Quitar</button>
            </div>
            <input type="hidden" name="devices[{{ $index }}][id]" value="{{ $deviceItem['id'] ?? '' }}">
            <div class="form-grid">
                <label>Tipo
                    <select name="devices[{{ $index }}][type]" required>
                        @foreach (['Notebook', 'PC', 'All-in-one', 'Otro'] as $type)
                            <option value="{{ $type }}" @selected(($deviceItem['type'] ?? 'Notebook') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Marca
                    <input name="devices[{{ $index }}][brand]" value="{{ $deviceItem['brand'] ?? '' }}">
                </label>
                <label>Modelo
                    <input name="devices[{{ $index }}][model]" value="{{ $deviceItem['model'] ?? '' }}">
                </label>
                <label>Número de serie
                    <input name="devices[{{ $index }}][serial_number]" value="{{ $deviceItem['serial_number'] ?? '' }}">
                </label>
                <label class="full">Accesorios recibidos
                    <textarea name="devices[{{ $index }}][accessories]">{{ $deviceItem['accessories'] ?? '' }}</textarea>
                </label>
                <label class="full">Detalle Técnico
                    <textarea name="devices[{{ $index }}][physical_condition]">{{ $deviceItem['physical_condition'] ?? '' }}</textarea>
                </label>
            </div>
        </div>
    @endforeach
</div>

@if (! isset($order))
<div class="panel" style="margin-top:24px;">
    <label style="display:flex; align-items:center; gap:10px;">
        <input id="attach_photos" type="checkbox" name="attach_photos" value="1" style="width:auto;" @checked(old('attach_photos'))>
        Adjuntar fotografías del estado de recepción
    </label>
    <div id="photo_fields" hidden style="margin-top:14px;">
        <input id="label_photo_index" type="hidden" name="label_photo_index" value="">
        <div class="actions" style="margin-top:0;">
            <label class="btn" style="margin:0;">Seleccionar imágenes
                <input id="order_photos_picker" type="file" accept="image/jpeg,image/png,image/webp" multiple hidden>
            </label>
            <button id="start_camera" class="btn" type="button">Usar webcam</button>
            <span id="photo_count" class="muted">0 de 5 fotografías</span>
        </div>
        <div id="camera_panel" hidden style="margin-top:12px;">
            <video id="camera_video" autoplay playsinline muted style="width:min(520px,100%); border-radius:8px; background:#111;"></video>
            <div class="actions">
                <button id="capture_camera" class="btn primary" type="button">Tomar fotografía</button>
                <button id="stop_camera" class="btn" type="button">Cerrar cámara</button>
            </div>
        </div>
        <input id="order_photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple hidden>
        <p class="muted">Puedes combinar capturas de webcam con archivos existentes. Marca debajo de la miniatura cuál corresponde a la etiqueta del notebook.</p>
        <div id="photo_preview" class="photo-preview"></div>
        <div class="actions">
            <button id="analyze_label" class="btn" type="button">Analizar etiqueta con IA</button>
            <span id="ai_status" class="muted"></span>
        </div>
    </div>
</div>
<style>
    .photo-preview { display:grid; grid-template-columns:repeat(5,minmax(90px,1fr)); gap:10px; margin-top:12px; }
    .photo-card { padding:8px; border:1px solid var(--line); border-radius:8px; background:#fff; }
    .photo-card img { width:100%; height:110px; object-fit:cover; border:1px solid var(--line); border-radius:6px; }
    .photo-card label { display:flex; align-items:center; gap:6px; margin:7px 0; font-size:12px; }
    .photo-card input { width:auto; min-height:auto; }
    .photo-card .btn { width:100%; min-height:32px; font-size:12px; }
    @media(max-width:760px){ .photo-preview{grid-template-columns:repeat(2,1fr);} }
</style>
<script>
    (() => {
        const enabled = document.getElementById('attach_photos');
        const fields = document.getElementById('photo_fields');
        const input = document.getElementById('order_photos');
        const picker = document.getElementById('order_photos_picker');
        const preview = document.getElementById('photo_preview');
        const analyze = document.getElementById('analyze_label');
        const status = document.getElementById('ai_status');
        const labelIndex = document.getElementById('label_photo_index');
        const count = document.getElementById('photo_count');
        const cameraPanel = document.getElementById('camera_panel');
        const video = document.getElementById('camera_video');
        let files = [];
        let stream = null;
        const toggle = () => { fields.hidden = !enabled.checked; input.required = enabled.checked; };
        enabled.addEventListener('change', toggle); toggle();

        const syncInput = () => {
            const transfer = new DataTransfer(); files.forEach((file) => transfer.items.add(file)); input.files = transfer.files;
            count.textContent = `${files.length} de 5 fotografías`;
        };
        const render = () => {
            preview.innerHTML = '';
            files.forEach((file, index) => {
                const card = document.createElement('div'); card.className = 'photo-card';
                const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = file.name;
                const label = document.createElement('label');
                const radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'label_photo_choice'; radio.checked = labelIndex.value === String(index);
                radio.addEventListener('change', () => { labelIndex.value = String(index); });
                label.append(radio, document.createTextNode('Etiqueta del notebook'));
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn danger'; remove.textContent = 'Eliminar imagen';
                remove.addEventListener('click', () => {
                    const selected = Number(labelIndex.value);
                    files.splice(index, 1);
                    if (selected === index) labelIndex.value = ''; else if (selected > index) labelIndex.value = String(selected - 1);
                    syncInput(); render();
                });
                card.append(image, label, remove); preview.append(card);
            });
        };
        const addFiles = (newFiles) => {
            const available = 5 - files.length;
            files.push(...[...newFiles].slice(0, available));
            if (newFiles.length > available) alert('Solo se permiten 5 fotografías por orden.');
            syncInput(); render(); picker.value = '';
        };
        picker.addEventListener('change', () => addFiles(picker.files));

        document.getElementById('start_camera').addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'environment', width:{ideal:1600} }, audio:false });
                video.srcObject = stream; cameraPanel.hidden = false;
            } catch (error) { status.textContent = 'No fue posible abrir la webcam. Revisa los permisos del navegador.'; }
        });
        const stopCamera = () => { stream?.getTracks().forEach((track) => track.stop()); stream = null; video.srcObject = null; cameraPanel.hidden = true; };
        document.getElementById('stop_camera').addEventListener('click', stopCamera);
        document.getElementById('capture_camera').addEventListener('click', () => {
            if (!stream || files.length >= 5) { if (files.length >= 5) alert('Ya alcanzaste el máximo de 5 fotografías.'); return; }
            const canvas = document.createElement('canvas'); canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            canvas.toBlob((blob) => { if (blob) addFiles([new File([blob], `webcam-${Date.now()}.jpg`, {type:'image/jpeg'})]); }, 'image/jpeg', .9);
        });
        analyze.addEventListener('click', async () => {
            const selected = Number(labelIndex.value);
            if (labelIndex.value === '' || !files[selected]) { status.textContent = 'Marca primero cuál imagen corresponde a la etiqueta.'; return; }
            analyze.disabled = true; status.textContent = 'Analizando etiqueta...';
            const body = new FormData(); body.append('company_id', document.getElementById('company_id').value); body.append('image', files[selected]);
            try {
                const response = await fetch('{{ route('ai.analyze-device-label') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'No fue posible analizar la etiqueta.');
                const first = document.querySelector('[data-device-block]');
                if (result.data.brand) first.querySelector('[name$="[brand]"]').value = result.data.brand;
                if (result.data.model) first.querySelector('[name$="[model]"]').value = result.data.model;
                if (result.data.serial_number) first.querySelector('[name$="[serial_number]"]').value = result.data.serial_number;
                status.textContent = `Datos cargados. Confianza: ${Math.round((result.data.confidence || 0) * 100)}%. Revisa antes de guardar.`;
            } catch (error) { status.textContent = error.message; }
            finally { analyze.disabled = false; }
        });
        window.addEventListener('beforeunload', stopCamera);
    })();
</script>
@endif

<div class="actions">
    <button class="btn primary" type="submit">Guardar</button>
    <a class="btn" href="{{ route('repair-orders.index') }}">Volver</a>
</div>

<template id="device_template">
    <div class="panel device-block" data-device-block>
        <div class="page-head" style="margin-bottom:12px;">
            <h2 style="margin:0;">Equipo __NUMBER__</h2>
            <button class="btn remove-device" type="button">Quitar</button>
        </div>
        <input type="hidden" name="devices[__INDEX__][id]" value="">
        <div class="form-grid">
            <label>Tipo
                <select name="devices[__INDEX__][type]" required>
                    <option value="Notebook">Notebook</option>
                    <option value="PC">PC</option>
                    <option value="All-in-one">All-in-one</option>
                    <option value="Otro">Otro</option>
                </select>
            </label>
            <label>Marca
                <input name="devices[__INDEX__][brand]">
            </label>
            <label>Modelo
                <input name="devices[__INDEX__][model]">
            </label>
            <label>Número de serie
                <input name="devices[__INDEX__][serial_number]">
            </label>
            <label class="full">Accesorios recibidos
                <textarea name="devices[__INDEX__][accessories]"></textarea>
            </label>
            <label class="full">Detalle Técnico
                <textarea name="devices[__INDEX__][physical_condition]"></textarea>
            </label>
        </div>
    </div>
</template>

<script>
    (() => {
        const mode = document.getElementById('service_mode');
        const company = document.getElementById('company_id');
        const fields = document.getElementById('direct_service_fields');
        const service = document.getElementById('service_id');
        const quantity = document.getElementById('service_quantity');
        const price = document.getElementById('service_unit_price');
        const discount = document.getElementById('discount_amount');
        const acceptance = document.getElementById('customer_acceptance');
        const net = document.getElementById('direct_service_net');
        const tax = document.getElementById('direct_service_tax');
        const total = document.getElementById('direct_service_total');
        const description = document.getElementById('service_description');

        const calculate = () => {
            const netValue = Math.max((Number(quantity.value) || 0) * (Number(price.value) || 0) - (Number(discount.value) || 0), 0);
            const taxValue = Math.round(netValue * 0.19);
            const money = (value) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);
            net.textContent = money(netValue);
            tax.textContent = money(taxValue);
            total.textContent = money(netValue + taxValue);
        };
        const refreshMode = () => {
            const direct = mode.value === 'precio_definido';
            fields.hidden = !direct;
            [service, quantity, price, acceptance].forEach((field) => field.required = direct);
        };
        const filterServices = () => {
            [...service.options].forEach((option) => {
                if (!option.value) return;
                option.hidden = Boolean(company.value) && option.dataset.company !== company.value;
                option.disabled = option.hidden;
            });
            if (service.selectedOptions[0]?.disabled) service.value = '';
        };
        service.addEventListener('change', () => {
            const option = service.selectedOptions[0];
            if (option?.value) price.value = option.dataset.price;
            description.textContent = option?.dataset.description || '';
            calculate();
        });
        [quantity, price, discount].forEach((field) => field.addEventListener('input', calculate));
        mode.addEventListener('change', refreshMode);
        company.addEventListener('change', filterServices);
        filterServices(); refreshMode(); calculate();
        description.textContent = service.selectedOptions[0]?.dataset.description || '';
    })();
</script>

<script>
    (() => {
        const container = document.getElementById('devices_container');
        const button = document.getElementById('add_device');
        const template = document.getElementById('device_template');

        const refreshDeviceBlocks = () => {
            container.querySelectorAll('[data-device-block]').forEach((block, index) => {
                block.querySelector('h2').textContent = `Equipo ${index + 1}`;
                block.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.name = field.name.replace(/devices\[\d+\]/, `devices[${index}]`);
                });

                const remove = block.querySelector('.remove-device');
                remove.style.display = index === 0 ? 'none' : 'inline-flex';
            });
        };

        button.addEventListener('click', () => {
            const index = container.querySelectorAll('[data-device-block]').length;
            const html = template.innerHTML
                .replaceAll('__INDEX__', String(index))
                .replaceAll('__NUMBER__', String(index + 1));

            container.insertAdjacentHTML('beforeend', html);
            refreshDeviceBlocks();
        });

        container.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-device')) {
                return;
            }

            event.target.closest('[data-device-block]').remove();
            refreshDeviceBlocks();
        });

        refreshDeviceBlocks();
    })();
</script>

<script>
    (() => {
        const company = document.getElementById('company_id');
        const rut = document.getElementById('client_rut');
        const rutError = document.getElementById('client_rut_error');
        const status = document.getElementById('client_lookup_status');
        const fields = {
            id: document.getElementById('client_id'),
            name: document.getElementById('client_name'),
            address: document.getElementById('client_address'),
            phone: document.getElementById('client_phone'),
            email: document.getElementById('client_email'),
            notes: document.getElementById('client_notes'),
        };
        const lockedFields = document.querySelectorAll('[data-rut-locked]');

        let timer = null;

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
            const value = rut.value.trim();

            if (!value) {
                rut.setCustomValidity('');
                rutError.textContent = '';
                return false;
            }

            const valid = isValidRut(value);
            rut.setCustomValidity(valid ? '' : 'Ingresa un RUT válido.');
            rutError.textContent = valid ? '' : 'RUT inválido. Revisa el número y dígito verificador.';

            return valid;
        };

        const setClientFieldsLocked = (locked) => {
            lockedFields.forEach((field) => {
                field.disabled = locked;
            });
        };

        const refreshClientFieldLock = () => {
            const valid = validateRutField();
            setClientFieldsLocked(!valid);

            if (!rut.value.trim()) {
                status.textContent = 'Ingresa un RUT válido para habilitar los datos del cliente.';
            } else if (!valid) {
                status.textContent = '';
            }

            return valid;
        };

        const clearKnownClient = () => {
            fields.id.value = '';
            status.textContent = '';
        };

        const lookupClient = async () => {
            const companyId = company.value;
            const rutValue = rut.value.trim();

            clearKnownClient();

            if (!refreshClientFieldLock()) {
                return;
            }

            if (!companyId || rutValue.length < 4) {
                return;
            }

            status.textContent = 'Buscando cliente...';

            try {
                const params = new URLSearchParams({ company_id: companyId, rut: rutValue });
                const response = await fetch(`{{ route('clients.lookup-by-rut') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    status.textContent = 'No se pudo consultar el RUT.';
                    return;
                }

                const data = await response.json();

                if (!data.found) {
                    status.textContent = 'Cliente nuevo. Completa sus datos para registrarlo con la orden.';
                    return;
                }

                fields.id.value = data.client.id ?? '';
                fields.name.value = data.client.name ?? '';
                fields.address.value = data.client.address ?? '';
                fields.phone.value = data.client.phone ?? '';
                fields.email.value = data.client.email ?? '';
                if (fields.notes) {
                    fields.notes.value = data.client.notes ?? '';
                }
                window.dispatchEvent(new CustomEvent('client-location-loaded', { detail: {
                    region: data.client.region ?? '',
                    commune: data.client.commune ?? '',
                }}));
                status.textContent = 'Cliente encontrado. Datos cargados automaticamente.';
            } catch (error) {
                status.textContent = 'No se pudo consultar el RUT.';
            }
        };

        rut.addEventListener('input', () => {
            rut.value = formatRut(rut.value);
            refreshClientFieldLock();
            clearTimeout(timer);
            timer = setTimeout(lookupClient, 450);
        });

        rut.addEventListener('blur', lookupClient);
        company.addEventListener('change', lookupClient);
        rut.value = formatRut(rut.value);
        refreshClientFieldLock();
    })();
</script>
