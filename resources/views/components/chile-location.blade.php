@props(['region' => '', 'commune' => '', 'locked' => false])
@php($locationId = 'location_'.uniqid())
<label>Región
    <select id="{{ $locationId }}_region" name="region" @if($locked) data-rut-locked @endif>
        <option value="">Seleccionar región</option>
        @foreach (config('chile.regions') as $regionName => $communes)
            <option value="{{ $regionName }}" @selected(old('region', $region) === $regionName)>{{ $regionName }}</option>
        @endforeach
    </select>
</label>
<label>Comuna
    <select id="{{ $locationId }}_commune" name="commune" data-selected="{{ old('commune', $commune) }}" @if($locked) data-rut-locked @endif>
        <option value="">Seleccionar comuna</option>
    </select>
</label>
<script>
    (() => {
        const regions = @json(config('chile.regions'));
        const region = document.getElementById('{{ $locationId }}_region');
        const commune = document.getElementById('{{ $locationId }}_commune');
        const refresh = () => {
            const selected = commune.dataset.selected || commune.value;
            commune.innerHTML = '<option value="">Seleccionar comuna</option>';
            (regions[region.value] || []).forEach((name) => {
                const option = new Option(name, name, false, name === selected);
                commune.add(option);
            });
            commune.dataset.selected = '';
        };
        region.addEventListener('change', () => { commune.dataset.selected = ''; refresh(); });
        window.addEventListener('client-location-loaded', (event) => {
            region.value = event.detail.region || '';
            commune.dataset.selected = event.detail.commune || '';
            refresh();
        });
        refresh();
    })();
</script>
