@extends('layouts.app')
@section('title', 'Funil')

@section('content')
<div class="page-header">
    <h1>Funil de Conversão — {{ auth()->user()->company->name }}</h1>
    <p>Distribuição de leads por etapa do atendimento</p>
</div>

{{-- Cards de resumo --}}
<div class="funil-cards" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="card">
        <div class="card-label">Total de leads</div>
        <div class="card-value">{{ number_format($total) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Encaminhados</div>
        <div class="card-value" style="color:#0dcaf0;">
            {{ number_format(collect($funnel)->firstWhere('status','encaminhado')['count'] ?? 0) }}
        </div>
    </div>
    <div class="card">
        <div class="card-label">Perdidos</div>
        <div class="card-value" style="color:#ff6584;">{{ number_format($lost) }}</div>
    </div>
    <div class="card" style="border-color:rgba(67,233,123,0.2);">
        <div class="card-label">Taxa de recuperação</div>
        <div class="card-value" style="color:#43e97b;">{{ $recoveryRate }}%</div>
    </div>
</div>

<div class="funil-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">

    {{-- Funil visual --}}
    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:20px;">Distribuição por status</div>
        @foreach($funnel as $step)
        <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $step['color'] }};flex-shrink:0;"></div>
                    <span style="font-size:13px;color:var(--muted2);">{{ $step['label'] }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:var(--muted);">{{ $step['pct'] }}%</span>
                    <span style="font-size:13px;font-weight:600;">{{ number_format($step['count']) }}</span>
                </div>
            </div>
            <div style="background:var(--surface2);border-radius:4px;height:8px;overflow:hidden;">
                <div style="height:100%;width:{{ $step['pct'] }}%;background:{{ $step['color'] }};border-radius:4px;transition:width 0.6s ease;"></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Gráfico de rosca --}}
    <div class="card funil-donut-card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <div style="font-size:14px;font-weight:600;margin-bottom:20px;align-self:flex-start;">Proporção visual</div>
        <div style="position:relative;width:180px;height:180px;">
            <canvas id="funnelDonut"></canvas>
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <div style="font-size:26px;font-weight:700;">{{ $total }}</div>
                <div style="font-size:11px;color:var(--muted);">leads</div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;justify-content:center;">
            @foreach($funnel as $step)
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:{{ $step['color'] }};flex-shrink:0;"></div>
                <span style="font-size:11px;color:var(--muted);">{{ $step['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Mapa de leads por cidade --}}
<div class="card" style="margin-top:20px;padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:14px;font-weight:600;margin-bottom:4px;">Leads por região</div>
                <div style="font-size:12px;color:var(--muted);">Cada círculo representa uma cidade. O tamanho indica o volume de leads.</div>
            </div>
            {{-- Legenda de cores --}}
            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#a855f7;"></span>
                    <span style="font-size:11px;color:var(--muted2);">Alto volume <span style="color:var(--muted);">(+70% do máximo)</span></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ec4899;"></span>
                    <span style="font-size:11px;color:var(--muted2);">Volume médio <span style="color:var(--muted);">(40–70%)</span></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#0dcaf0;"></span>
                    <span style="font-size:11px;color:var(--muted2);">Baixo volume <span style="color:var(--muted);">(até 40%)</span></span>
                </div>
            </div>
        </div>
    </div>
    <div style="display:grid;gap:0;align-items:start;" class="funil-map-grid">
        <div id="funil-map" style="height:380px;min-height:380px;overflow:hidden;"></div>
        <div style="display:flex;flex-direction:column;gap:8px;max-height:380px;overflow-y:auto;padding:16px;border-left:1px solid var(--border);">
            @php $maxCity = $leads_by_city->max('count') ?: 1; @endphp
            @forelse($leads_by_city->take(10) as $i => $city)
            <div style="padding:10px 12px;background:var(--surface2);border-radius:8px;border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;">
                        <span style="width:18px;height:18px;border-radius:50%;background:rgba(168,85,247,0.2);color:#a855f7;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;">{{ $i+1 }}</span>
                        {{ $city->city }}
                    </span>
                    <span style="font-size:11px;font-weight:700;color:#a855f7;">{{ number_format($city->count) }}</span>
                </div>
                <div style="background:var(--border);border-radius:4px;height:4px;overflow:hidden;">
                    <div style="height:100%;width:{{ round(($city->count / $maxCity) * 100) }}%;background:linear-gradient(90deg,#a855f7,#ec4899);border-radius:4px;"></div>
                </div>
                <div style="font-size:10px;color:var(--muted);margin-top:4px;">{{ $city->count }} {{ $city->count == 1 ? 'lead' : 'leads' }}</div>
            </div>
            @empty
            <div style="text-align:center;padding:32px;color:var(--muted);font-size:13px;">Nenhum lead com cidade preenchida</div>
            @endforelse
        </div>
    </div>
</div>

<style>
.funil-map-grid { grid-template-columns: 1fr 280px; }
@media (max-width: 900px) {
    .funil-map-grid { grid-template-columns: 1fr !important; }
    #funil-map { height: 280px !important; }
}
@media (max-width: 768px) {
    .funil-cards         { grid-template-columns: repeat(2,1fr) !important; }
    .funil-grid          { grid-template-columns: 1fr !important; }
}
@media (max-width: 480px) {
    .funil-cards  { grid-template-columns: 1fr 1fr !important; }
    .card-value   { font-size: 22px !important; }
}
</style>

@endsection

@push('scripts')
<script>
const funnelData = @json($funnel);

new Chart(document.getElementById('funnelDonut'), {
    type: 'doughnut',
    data: {
        labels: funnelData.map(s => s.label),
        datasets: [{
            data: funnelData.map(s => s.count),
            backgroundColor: funnelData.map(s => s.color),
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} leads (${funnelData[ctx.dataIndex].pct}%)`
                }
            }
        }
    }
});

// ── Mapa de leads por cidade ──────────────────────────────────────────────
const leadsByCity = @json($leads_by_city);

// Cache com localStorage — persiste entre recarregamentos
const _coordsCache = JSON.parse(localStorage.getItem('cityCoords') || '{}');

async function getCoordsForCity(name) {
    if (!name) return null;
    const key = name.trim().toLowerCase();

    // Se já tem no cache do navegador, retorna direto
    if (_coordsCache[key] !== undefined) return _coordsCache[key];

    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(name + ', Brasil')}&format=json&limit=1`,
            { headers: { 'Accept-Language': 'pt-BR' } }
        );
        const data = await res.json();
        if (data.length > 0) {
            const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
            _coordsCache[key] = coords;
            localStorage.setItem('cityCoords', JSON.stringify(_coordsCache));
            return coords;
        }
    } catch(e) {}

    _coordsCache[key] = null;
    localStorage.setItem('cityCoords', JSON.stringify(_coordsCache));
    return null;
}

window.addEventListener('load', async function() {
    const L = window.L;
    if (!L) return;

    if (leadsByCity.length > 0) {
        const map = L.map('funil-map', { zoomControl: true, scrollWheelZoom: false })
            .setView([-15.7801, -47.9292], 4);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors', maxZoom: 18
        }).addTo(map);

        setTimeout(() => map.invalidateSize(), 300);
        setTimeout(() => map.invalidateSize(), 800);

        const maxVal = Math.max(...leadsByCity.map(c => c.count));

        for (const city of leadsByCity) {
            const coords = await getCoordsForCity(city.city);
            if (!coords) continue;

            const pct   = city.count / maxVal;
            const color = pct > 0.7 ? '#a855f7' : pct > 0.4 ? '#ec4899' : '#0dcaf0';
            L.circleMarker(coords, {
                radius: 10 + pct * 35, fillColor: color, color, weight: 1,
                opacity: 0.9, fillOpacity: 0.35,
            }).addTo(map).bindPopup(
                `<div style="font-family:Inter,sans-serif;font-size:13px;min-width:140px;">
                    <strong>${city.city}</strong><br>
                    <span style="color:#a855f7;font-weight:700;">${city.count} ${city.count == 1 ? 'lead' : 'leads'}</span>
                </div>`
            );
        }
    } else {
        document.getElementById('funil-map').innerHTML =
            '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#6b6b90;font-size:13px;">Nenhum lead com cidade preenchida</div>';
    }
});
</script>
@endpush