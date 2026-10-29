@extends('layouts.app')
@section('content')
<script>document.addEventListener('DOMContentLoaded',()=>{const area='{{$area}}',select=document.querySelector('select[name="service_area"]'),form=select?.form;if(select&&form){select.value=area;select.disabled=true;const hidden=document.createElement('input');hidden.type='hidden';hidden.name='service_area';hidden.value=area;form.appendChild(hidden)}const back=document.querySelector('.page-head>a.btn');if(back)back.href='{{route('home-service.index',['area'=>$area])}}'})</script>
<div class="page-head"><div><h1>Nueva preorden</h1><p class="muted">Importa la estimación del sitio y registra el prediagnóstico realizado por WhatsApp.</p></div><a class="btn" href="{{route('home-service.index')}}">Volver</a></div><div class="panel"><form method="post" action="{{route('home-service.store')}}">@csrf<div class="form-grid"><label>Empresa<select id="company" name="company_id">@foreach($companies as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></label><label>Código de estimación<div class="inline"><input id="quote-code" name="external_quote_code" value="{{old('external_quote_code')}}" placeholder="RET-XXXXXXXXXX" required><button id="lookup" class="btn" type="button">Buscar</button></div><small id="lookup-status" class="muted"></small></label></div><div id="quote-card" class="quote-card" hidden><h2>Estimación encontrada</h2><div class="quote-grid"><span>Dirección<b id="q-address"></b></span><span>Distancia<b id="q-distance"></b></span><span>Retiro<b id="q-pickup"></b></span><span>Entrega<b id="q-delivery"></b></span><span>Total<b id="q-total"></b></span><span>Vigencia<b id="q-expiry"></b></span></div></div>
<h2 class="section">Cliente y prediagnóstico</h2><div class="form-grid"><label>RUT<input id="client-rut" name="client_rut" value="{{old('client_rut')}}" required autocomplete="off"></label><label>Nombre<input id="client-name" name="client_name" value="{{old('client_name')}}" required></label><label>Teléfono<input id="client-phone" name="client_phone" value="{{old('client_phone')}}" required></label><label>Correo<input id="client-email" type="email" name="client_email" value="{{old('client_email')}}"></label><label class="full">Dirección del cliente<input id="client-address" name="client_address" value="{{old('client_address')}}"></label><p id="client-lookup-status" class="muted full" style="margin:0;"></p><label>Área<select name="service_area"><option value="servicio_tecnico">Servicio Técnico</option><option value="recuperadata">Recuperadata</option></select></label><label>Tipo preliminar de equipo<input name="unit_type" value="{{old('unit_type')}}" placeholder="Notebook, PC, HDD..."></label><label class="full">Problema informado<textarea name="reported_problem" required>{{old('reported_problem')}}</textarea></label><label class="full">Resumen de conversación por WhatsApp<textarea name="conversation_summary" required>{{old('conversation_summary')}}</textarea></label><label class="full">Prediagnóstico del consultor<textarea name="prediagnosis" required>{{old('prediagnosis')}}</textarea></label><label>Técnico asignado<select name="assigned_user_id"><option value="">Pendiente</option>@foreach($users as $u)<option value="{{$u->id}}">{{$u->name}}</option>@endforeach</select></label><label>Fecha y hora del retiro<input type="datetime-local" name="scheduled_at" value="{{old('scheduled_at')}}"></label><label>Franja horaria<input name="time_window" value="{{old('time_window')}}" placeholder="Ej.: 15:00 a 17:00"></label></div><div class="actions"><button class="btn primary">Crear preorden</button></div></form></div>
<style>.inline{display:flex;gap:7px}.inline input{flex:1}.quote-card{margin:18px 0;padding:16px;background:#eff6ff;border:1px solid #93c5fd;border-radius:8px}.quote-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.quote-grid span{color:var(--muted)}.quote-grid b{display:block;margin-top:4px;color:var(--ink)}.section{margin-top:24px}@media(max-width:760px){.quote-grid{grid-template-columns:1fr 1fr}}</style>
<script>(()=>{const button=document.getElementById('lookup'),status=document.getElementById('lookup-status'),card=document.getElementById('quote-card'),money=n=>new Intl.NumberFormat('es-CL',{style:'currency',currency:'CLP',maximumFractionDigits:0}).format(n);button.onclick=async()=>{status.textContent='Consultando WordPress...';card.hidden=true;const body=new FormData();body.append('company_id',document.getElementById('company').value);body.append('code',document.getElementById('quote-code').value);try{const r=await fetch('{{route('home-service.lookup')}}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{csrf_token()}}','Accept':'application/json'},body});const d=await r.json();if(!r.ok)throw new Error(d.message||'No fue posible consultar.');document.getElementById('q-address').textContent=d.address;document.getElementById('q-distance').textContent=d.distance_km+' km';document.getElementById('q-pickup').textContent=money(d.pickup_amount);document.getElementById('q-delivery').textContent=money(d.delivery_amount);document.getElementById('q-total').textContent=money(d.total_amount);document.getElementById('q-expiry').textContent=new Date(d.expires_at).toLocaleDateString('es-CL');document.getElementById('client-address').value=d.address;card.hidden=false;status.textContent='Estimación válida.'}catch(e){status.textContent=e.message}}})()</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const company = document.getElementById('company');
    const rut = document.getElementById('client-rut');
    const status = document.getElementById('client-lookup-status');
    const fields = {
        name: document.getElementById('client-name'),
        phone: document.getElementById('client-phone'),
        email: document.getElementById('client-email'),
        address: document.getElementById('client-address'),
    };
    let timer;

    const formatRut = value => {
        const clean = value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (clean.length < 2) return clean;
        const body = clean.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return `${body}-${clean.slice(-1)}`;
    };

    const lookupClient = async () => {
        const rutValue = rut.value.trim();
        if (!company.value || rutValue.replace(/[^0-9kK]/g, '').length < 2) {
            status.textContent = '';
            return;
        }

        status.textContent = 'Buscando cliente...';
        const params = new URLSearchParams({ company_id: company.value, rut: rutValue });

        try {
            const response = await fetch(`{{ route('clients.lookup-by-rut') }}?${params}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                status.textContent = response.status === 422 ? 'Ingresa un RUT válido.' : 'No se pudo consultar el cliente.';
                return;
            }

            const data = await response.json();
            if (!data.found) {
                status.textContent = 'Cliente nuevo. Completa sus datos para registrarlo.';
                return;
            }

            fields.name.value = data.client.name ?? '';
            fields.phone.value = data.client.phone ?? '';
            fields.email.value = data.client.email ?? '';
            fields.address.value = data.client.address ?? '';
            status.textContent = 'Cliente encontrado. Datos cargados automáticamente.';
        } catch (error) {
            status.textContent = 'No se pudo consultar el cliente.';
        }
    };

    rut.addEventListener('input', () => {
        rut.value = formatRut(rut.value);
        clearTimeout(timer);
        timer = setTimeout(lookupClient, 450);
    });
    rut.addEventListener('blur', lookupClient);
    company.addEventListener('change', lookupClient);
});
</script>
@endsection
