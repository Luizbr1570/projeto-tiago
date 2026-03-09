@extends('layouts.app')
@section('title', 'Visão Geral')

@section('content')
<div class="page-header">
    <h1>Dashboard — {{ auth()->user()->company->name }}</h1>
    <p>Resumo do atendimento IA e performance de leads</p>
</div>

{{-- ── Cards principais ── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="card">
        <div class="card-label">Leads hoje</div>
        <div class="card-value">{{ $data['leads_today'] }}</div>
    </div>
    <div class="card">
        <div class="card-label">Leads no mês</div>
        <div class="card-value">{{ number_format($data['leads_month'], 0, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="card-label">Ticket médio</div>
        <div class="card-value" style="font-size:26px;">R$ {{ number_format($data['ticket_average'], 0, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="card-label">Tempo médio de resposta</div>
        <div class="card-value">{{ number_format($data['avg_response_time'], 2, ',', '.') }}s</div>
    </div>
</div>

{{-- ── Gráfico leads + Produto + Transferência + Dinheiro ── --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:14px;margin-bottom:20px;">

    {{-- Gráfico de linha --}}
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <span style="font-size:13px;font-weight:600;">Leads por dia (últimos 14 dias)</span>
            <span style="font-size:12px;color:var(--muted);">Total: {{ number_format($data['leads_month'], 0, ',', '.') }}</span>
        </div>
        <canvas id="leadsChart" height="130"></canvas>
    </div>

    {{-- Coluna direita --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Produto mais buscado --}}
        <div class="card" style="flex:1;">
            <div class="card-label">Produto mais buscado</div>
            <div style="font-size:20px;font-weight:700;margin:6px 0 12px;">
                {{ $data['top_products']->first()->name ?? '—' }}
            </div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:8px;font-weight:600;letter-spacing:0.5px;">Top 3 produtos</div>
            @foreach($data['top_products']->take(3) as $p)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                <span style="font-size:12px;color:var(--muted2);">{{ $p->name }}</span>
                <span style="font-size:12px;font-weight:600;">{{ $p->total }}</span>
            </div>
            @endforeach
        </div>

        {{-- Taxa de transferência + Dinheiro --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

            {{-- Donut transferência --}}
            <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <div class="card-label" style="text-align:center;margin-bottom:10px;">Taxa de transferência<br>para humano</div>
                <div style="position:relative;width:90px;height:90px;">
                    <canvas id="donutChart"></canvas>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;">
                        {{ $data['transfer_rate'] }}%
                    </div>
                </div>
            </div>

            {{-- Dinheiro gerado --}}
            <div class="card" style="position:relative;overflow:hidden;">
                <div class="card-label">Dinheiro gerado pela IA<br>(estimado)</div>
                <div style="font-size:22px;font-weight:700;color:var(--accent);margin-top:8px;line-height:1.1;">
                    R$ {{ number_format($data['revenue_with_ai'], 0, ',', '.') }}
                </div>
                <div style="position:absolute;bottom:0;left:0;right:0;height:28px;overflow:hidden;">
                    <canvas id="miniChart" height="28"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Funil de conversão ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px;">Funil de Conversão</div>
        @php $maxF = collect($data['funnel'])->max('value') ?: 1; @endphp
        @foreach($data['funnel'] as $i => $step)
        @php
            $pct = round(($step['value'] / $maxF) * 100);
            $colors = ['#a855f7','#9333ea','#7c3aed','#ec4899','#db2777'];
            $c = $colors[$i] ?? '#a855f7';
        @endphp
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;color:var(--muted);">{{ $step['label'] }}</span>
                <span style="font-size:12px;font-weight:600;">{{ number_format($step['value']) }}</span>
            </div>
            <div style="background:var(--surface2);border-radius:4px;height:8px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $c }};border-radius:4px;"></div>
            </div>
            @if(!$loop->last)
            <div style="text-align:center;font-size:10px;color:var(--muted);margin-top:3px;">↓</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Recuperação de leads --}}
    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px;">Recuperação de Leads</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div style="background:var(--surface2);border-radius:8px;padding:14px;border:1px solid var(--border);">
                <div style="font-size:10px;color:var(--muted);margin-bottom:4px;font-weight:600;">PERDIDOS</div>
                <div style="font-size:22px;font-weight:700;color:#ff6584;">{{ number_format($data['leads_lost']) }}</div>
            </div>
            <div style="background:var(--surface2);border-radius:8px;padding:14px;border:1px solid var(--border);">
                <div style="font-size:10px;color:var(--muted);margin-bottom:4px;font-weight:600;">RECUPERADOS</div>
                <div style="font-size:22px;font-weight:700;color:#43e97b;">{{ number_format($data['leads_recovered']) }}</div>
            </div>
        </div>
        <div style="background:rgba(168,85,247,0.06);border:1px solid rgba(168,85,247,0.2);border-radius:8px;padding:14px;">
            <div style="font-size:11px;color:var(--accent);font-weight:600;margin-bottom:4px;">💰 DINHEIRO RECUPERADO</div>
            <div style="font-size:22px;font-weight:700;">R$ {{ number_format($data['revenue_recovered'], 0, ',', '.') }}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px;">Taxa: {{ $data['recovery_rate'] }}%</div>
        </div>
    </div>
</div>

{{-- ── Horários de pico + Insights ── --}}
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:14px;">
    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px;">Horários de Pico</div>
        <canvas id="peakChart" height="90"></canvas>
    </div>
    <div class="card">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <i data-lucide="sparkles" style="width:15px;height:15px;color:var(--accent);"></i>
            <span style="font-size:14px;font-weight:600;">Insights da IA</span>
        </div>

        {{-- FIX B03: usando $data['recent_insights'] do controller (não query inline) --}}
        @forelse($data['recent_insights'] as $insight)
        <div style="display:flex;gap:8px;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
            <div style="width:5px;height:5px;border-radius:50%;background:var(--accent);margin-top:5px;flex-shrink:0;"></div>
            <p style="font-size:12px;color:var(--muted2);line-height:1.5;">{{ $insight->insight }}</p>
        </div>
        @empty
        <div style="text-align:center;padding:16px 0;">
            <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">Nenhum insight gerado ainda</p>
            @if(auth()->user()->role === 'admin')
            <form method="POST" action="{{ route('insights.store') }}">
                @csrf
                <button type="submit" class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">
                    <i data-lucide="zap" style="width:12px;height:12px;"></i> Gerar agora
                </button>
            </form>
            @endif
        </div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
const leadsPerDay   = @json($data['leads_per_day']);
const peakHours     = @json($data['peak_hours']);
const topProducts   = @json($data['top_products']);

// Gráfico linha leads
new Chart(document.getElementById('leadsChart'), {
    type: 'line',
    data: {
        labels: leadsPerDay.map(d => d.date),
        datasets: [{
            data: leadsPerDay.map(d => d.total),
            borderColor: '#a855f7',
            backgroundColor: 'rgba(168,85,247,0.1)',
            borderWidth: 2, fill: true, tension: 0.4,
            pointBackgroundColor: '#a855f7', pointRadius: 4, pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(42,42,69,0.5)' }, ticks: { color: '#6b6b90', font: { size: 11 } } },
            y: { grid: { color: 'rgba(42,42,69,0.5)' }, ticks: { color: '#6b6b90', font: { size: 11 } }, beginAtZero: true }
        }
    }
});

// Donut transferência
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [{{ $data['transfer_rate'] }}, {{ 100 - $data['transfer_rate'] }}],
            backgroundColor: ['#a855f7', '#2a2a45'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});

// Mini gráfico receita
new Chart(document.getElementById('miniChart'), {
    type: 'line',
    data: {
        labels: leadsPerDay.map(d => d.date),
        datasets: [{
            data: leadsPerDay.map(d => d.total * {{ $data['ticket_average'] }} * 0.15),
            borderColor: '#ec4899', borderWidth: 1.5, fill: false, tension: 0.4,
            pointRadius: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } }
    }
});

// Horários de pico
new Chart(document.getElementById('peakChart'), {
    type: 'bar',
    data: {
        labels: peakHours.map(h => h.hour),
        datasets: [{
            data: peakHours.map(h => h.total),
            backgroundColor: 'rgba(168,85,247,0.5)',
            borderColor: '#a855f7', borderWidth: 1, borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(42,42,69,0.5)' }, ticks: { color: '#6b6b90', font: { size: 11 } } },
            y: { grid: { color: 'rgba(42,42,69,0.5)' }, ticks: { color: '#6b6b90', font: { size: 11 } }, beginAtZero: true }
        }
    }
});
</script>
@endpush