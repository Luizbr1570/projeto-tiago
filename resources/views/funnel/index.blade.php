@extends('layouts.app')
@section('title', 'Funil')

@section('content')
<div class="page-header">
    <h1>Funil de Conversão — {{ auth()->user()->company->name }}</h1>
    <p>Distribuição de leads por etapa do atendimento</p>
</div>

{{-- Cards de resumo --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">

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
    <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <div style="font-size:14px;font-weight:600;margin-bottom:20px;align-self:flex-start;">Proporção visual</div>
        <div style="position:relative;width:200px;height:200px;">
            <canvas id="funnelDonut"></canvas>
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <div style="font-size:28px;font-weight:700;">{{ $total }}</div>
                <div style="font-size:11px;color:var(--muted);">leads</div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px;justify-content:center;">
            @foreach($funnel as $step)
            <div style="display:flex;align-items:center;gap:5px;">
                <div style="width:8px;height:8px;border-radius:50%;background:{{ $step['color'] }};"></div>
                <span style="font-size:11px;color:var(--muted);">{{ $step['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Tabela detalhada --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <span style="font-size:14px;font-weight:600;">Detalhamento por status</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Quantidade</th>
                <th>% do total</th>
                <th>Visualização</th>
            </tr>
        </thead>
        <tbody>
            @foreach($funnel as $step)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $step['color'] }};"></div>
                        <span class="badge badge-{{ $step['status'] }}">{{ $step['label'] }}</span>
                    </div>
                </td>
                <td style="font-weight:600;">{{ number_format($step['count']) }}</td>
                <td style="color:var(--muted);">{{ $step['pct'] }}%</td>
                <td style="width:200px;">
                    <div style="background:var(--surface2);border-radius:4px;height:6px;overflow:hidden;">
                        <div style="height:100%;width:{{ $step['pct'] }}%;background:{{ $step['color'] }};border-radius:4px;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

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
</script>
@endpush