@extends('layouts.mobile')
@section('content')
<section class="mobile-title"><div><p class="eyebrow">Trabajo en terreno</p><h1>Retiros de {{$area==='recuperadata'?'Recuperadata':'Servicio Técnico'}}</h1><p class="muted">Selecciona una visita para registrar el equipo, fotografías y firma.</p></div><span class="count">{{$preorders->count()}}</span></section>
<div class="quick-filter"><button class="filter active" data-filter="all">Todos</button><button class="filter" data-filter="today">Hoy</button><button class="filter" data-filter="mine">Asignados a mí</button></div>
<section class="jobs" id="jobs">
@forelse($preorders as $p)
<article class="job-card" data-today="{{$p->scheduled_at?->isToday()?'1':'0'}}" data-mine="{{$p->assigned_user_id===auth()->id()?'1':'0'}}">
    <div class="job-top"><div><span class="code">{{$p->code}}</span><h2>{{$p->client?->name}}</h2></div><span class="badge">{{$p->status_label}}</span></div>
    <p class="address">⌖ {{$p->address}}</p>
    <div class="job-meta"><span><b>Agenda</b>{{$p->scheduled_at?->format('d-m H:i')?:'Sin fecha'}} {{$p->time_window}}</span><span><b>Equipo</b>{{$p->unit_type?:'Por confirmar'}}</span></div>
    <div class="job-actions"><a class="btn" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{urlencode($p->address)}}">Mapa</a><a class="btn primary" href="{{route('home-service.mobile',$p)}}">Iniciar recepción</a></div>
</article>
@empty
<div class="empty"><span>✓</span><h2>Sin retiros pendientes</h2><p class="muted">Las nuevas preórdenes aparecerán aquí.</p></div>
@endforelse
</section>
<style>.mobile-title{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:16px}.eyebrow{margin:0 0 5px;color:var(--brand2);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.mobile-title .muted{margin:7px 0}.count{display:grid;place-items:center;min-width:44px;height:44px;border-radius:14px;background:var(--brand);color:#fff;font-size:20px;font-weight:800}.quick-filter{display:flex;gap:7px;overflow:auto;padding-bottom:12px}.filter{white-space:nowrap;border:1px solid #b9c7d6;border-radius:99px;background:#fff;padding:9px 13px;font-weight:700;color:#526274}.filter.active{background:var(--brand);border-color:var(--brand);color:#fff}.jobs{display:grid;gap:12px}.job-card{background:#fff;border:1px solid var(--line);border-radius:15px;padding:15px;box-shadow:0 4px 16px #174f7512}.job-top{display:flex;justify-content:space-between;gap:10px}.job-top h2{margin:4px 0 0}.code{font-size:12px;font-weight:800;color:var(--brand2)}.address{padding:10px;background:#f4f8fb;border-radius:9px}.job-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px}.job-meta span{color:#475569}.job-meta b{display:block;color:#172033;margin-bottom:3px}.job-actions{display:grid;grid-template-columns:.8fr 1.4fr;gap:8px;margin-top:14px}.empty{text-align:center;padding:44px 16px;background:#fff;border-radius:15px}.empty>span{font-size:42px;color:var(--brand2)}</style>
<script>document.querySelectorAll('.filter').forEach(button=>button.onclick=()=>{document.querySelectorAll('.filter').forEach(x=>x.classList.remove('active'));button.classList.add('active');document.querySelectorAll('.job-card').forEach(card=>card.hidden=button.dataset.filter!=='all'&&card.dataset[button.dataset.filter]!=='1')})</script>
@endsection
