@extends('layouts.app')
@section('title', 'Relatórios')

@section('content')
<div class="page-header">
    <h1>Relatórios — {{ auth()->user()->company->name }}</h1>
    <p>Métricas diárias de performance</p>
</div>

{{-- Tabela — desktop --}}
<div class="metrics-table-desktop card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:14px;font-weight:600;">Métricas diárias</span>
    </div>
    <div style="overflow-x:auto;">
        <table style="min-width:520px;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Leads</th>
                    <th>Conversas</th>
                    <th>Recuperados</th>
                    <th>Receita estimada</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metrics as $m)
                <tr>
                    <td style="font-weight:500;">{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($m->leads) }}</td>
                    <td>{{ number_format($m->conversations) }}</td>
                    <td><span style="color:#43e97b;font-weight:600;">{{ number_format($m->recovered_leads) }}</span></td>
                    <td style="font-weight:600;color:#a855f7;">R$ {{ number_format($m->estimated_revenue, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma métrica ainda</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação desktop --}}
    @if($metrics->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface),var(--surface2));display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            📊 Mostrando <strong style="color:var(--accent);">{{ $metrics->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $metrics->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $metrics->total() }}</strong> registros
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if($metrics->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">← Anterior</span>
            @else
                <a href="{{ $metrics->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">← Anterior</a>
            @endif
            @php
                $current = $metrics->currentPage();
                $last    = $metrics->lastPage();
                $from    = max(1, $current - 2);
                $to      = min($last, $current + 2);
                if ($to - $from < 4 && $last > 4) {
                    if ($from === 1) { $to = min($last, 5); }
                    elseif ($to === $last) { $from = max(1, $last - 4); }
                }
            @endphp
            <div style="display:flex;gap:4px;align-items:center;">
                @if($from > 1)
                    <a href="{{ $metrics->url(1) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">1</a>
                    @if($from > 2)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                @endif
                @for($page = $from; $page <= $to; $page++)
                    @if($page == $current)
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">{{ $page }}</span>
                    @else
                        <a href="{{ $metrics->url($page) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $page }}</a>
                    @endif
                @endfor
                @if($to < $last)
                    @if($to < $last - 1)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                    <a href="{{ $metrics->url($last) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $last }}</a>
                @endif
            </div>
            @if($metrics->hasMorePages())
                <a href="{{ $metrics->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                   onmouseover="this.style.transform='translateX(2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">Próxima →</a>
            @else
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Cards — mobile --}}
<div class="metrics-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($metrics as $m)
    <div class="card" style="padding:14px 16px;">
        {{-- Data em destaque --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="calendar" style="width:13px;height:13px;color:#a855f7;"></i>
                </div>
                <span style="font-size:14px;font-weight:700;">{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</span>
            </div>
            <span style="font-size:14px;font-weight:700;color:#a855f7;">R$ {{ number_format($m->estimated_revenue, 2, ',', '.') }}</span>
        </div>

        {{-- Grid de métricas --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
            <div style="background:var(--surface2);border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:4px;">LEADS</div>
                <div style="font-size:18px;font-weight:700;">{{ number_format($m->leads) }}</div>
            </div>
            <div style="background:var(--surface2);border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:4px;">CONVERSAS</div>
                <div style="font-size:18px;font-weight:700;">{{ number_format($m->conversations) }}</div>
            </div>
            <div style="background:rgba(67,233,123,0.08);border:1px solid rgba(67,233,123,0.15);border-radius:8px;padding:10px;text-align:center;">
                <div style="font-size:10px;color:#43e97b;font-weight:600;margin-bottom:4px;">RECUPERADOS</div>
                <div style="font-size:18px;font-weight:700;color:#43e97b;">{{ number_format($m->recovered_leads) }}</div>
            </div>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma métrica ainda</div>
    @endforelse

    {{-- Paginação mobile --}}
    @if($metrics->hasPages())
    <div style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;color:var(--muted);">
            {{ $metrics->firstItem() }}–{{ $metrics->lastItem() }} de {{ $metrics->total() }}
        </div>
        <div style="display:flex;gap:8px;">
            @if($metrics->onFirstPage())
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $metrics->previousPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
            @endif
            @if($metrics->hasMorePages())
                <a href="{{ $metrics->nextPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
            @else
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
@media (max-width: 768px) {
    .metrics-table-desktop { display: none !important; }
    .metrics-cards-mobile  { display: flex !important; }
}
</style>

@endsection