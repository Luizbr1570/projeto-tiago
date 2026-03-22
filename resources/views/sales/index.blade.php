@extends('layouts.app')
@section('title', 'Vendas')

@section('content')

<div class="page-header sales-page-header">
    <div>
        <h1>Vendas — {{ auth()->user()->company->name }}</h1>
        <p>Histórico completo e análise de vendas</p>
    </div>
    <a href="{{ route('sales.export', ['period' => $period]) }}" class="btn btn-primary btn-export-sales">
        <i data-lucide="download" style="width:14px;height:14px;"></i>
        <span class="export-label">Exportar CSV</span>
    </a>
</div>

{{-- Filtros --}}
<div class="sales-filters">
    @foreach(['all' => 'Total', 'today' => 'Hoje', '7days' => '7 dias', '30days' => '30 dias'] as $val => $label)
    <a href="{{ request()->fullUrlWithQuery(['period' => $val]) }}"
        class="filter-btn {{ $period === $val ? 'active' : '' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- Cards resumo --}}
<div class="sales-summary">
    <div class="card">
        <div class="card-label">Total de vendas</div>
        <div class="card-value">{{ $total_count }}</div>
    </div>
    <div class="card">
        <div class="card-label">Valor total</div>
        <div class="card-value" style="font-size:22px;color:#43e97b;">R$ {{ number_format($total_value, 2, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="card-label">Ticket médio</div>
        <div class="card-value" style="font-size:22px;">
            R$ {{ $total_count > 0 ? number_format($total_value / $total_count, 2, ',', '.') : '0,00' }}
        </div>
    </div>
    <div class="card">
        <div class="card-label">Melhor dia</div>
        <div class="card-value" style="font-size:18px;color:var(--accent);">
            {{ $best_day ? \Carbon\Carbon::parse($best_day->day)->format('d/m/Y') : '—' }}
        </div>
        @if($best_day)
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">R$ {{ number_format($best_day->total, 2, ',', '.') }}</div>
        @endif
    </div>
</div>

{{-- Meta do mês --}}
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;gap:8px;">
        <div>
            <span style="font-size:13px;font-weight:600;">Meta estimada do mês</span>
            <div style="font-size:11px;color:var(--muted);margin-top:2px;">baseada na média dos últimos 30 dias</div>
        </div>
        <div style="font-size:13px;font-weight:700;white-space:nowrap;color:{{ $meta_pct >= 100 ? '#43e97b' : ($meta_pct >= 60 ? '#ffc107' : '#ff6584') }};">
            {{ $meta_pct }}% atingido
        </div>
    </div>
    <div style="background:var(--bg-card-hover,rgba(255,255,255,0.05));border-radius:8px;height:12px;overflow:hidden;">
        <div style="height:100%;width:{{ $meta_pct }}%;background:{{ $meta_pct >= 100 ? '#43e97b' : ($meta_pct >= 60 ? '#ffc107' : '#a855f7') }};border-radius:8px;transition:width .6s ease;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--muted);flex-wrap:wrap;gap:4px;">
        <span>R$ {{ number_format($vendido_mes, 2, ',', '.') }} vendido</span>
        <span>Meta: R$ {{ number_format($meta_mes, 2, ',', '.') }}</span>
    </div>
</div>

{{-- Gráfico principal: vendas por dia --}}
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <span style="font-size:13px;font-weight:600;">Vendas por dia</span>
        <span style="font-size:12px;color:var(--muted);">{{ $total_count }} vendas · R$ {{ number_format($total_value, 2, ',', '.') }}</span>
    </div>
    <canvas id="salesChart" height="80"></canvas>
</div>

{{-- Gráficos linha 2: por produto e por categoria --}}
<div class="charts-row">
    <div class="card">
        <div style="font-size:13px;font-weight:600;margin-bottom:16px;">Top produtos por receita</div>
        <canvas id="productChart" height="220"></canvas>
    </div>
    <div class="card">
        <div style="font-size:13px;font-weight:600;margin-bottom:16px;">Receita por categoria</div>
        <canvas id="categoryChart" height="220"></canvas>
    </div>
</div>

{{-- Gráficos linha 3: por origem e por hora --}}
<div class="charts-row">
    <div class="card">
        <div style="font-size:13px;font-weight:600;margin-bottom:16px;">Vendas por origem do lead</div>
        <canvas id="sourceChart" height="220"></canvas>
    </div>
    <div class="card">
        <div style="font-size:13px;font-weight:600;margin-bottom:16px;">Horário de pico de vendas</div>
        <canvas id="hourChart" height="220"></canvas>
    </div>
</div>

{{-- Mapa de vendas por cidade --}}
<div class="card" style="margin-bottom:20px;">
    <div style="margin-bottom:16px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:10px;">
            <div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Vendas por região</div>
                <div style="font-size:12px;color:var(--muted);">Cada círculo representa uma cidade. O tamanho indica o valor total vendido.</div>
            </div>
        </div>
        {{-- Legenda de cores --}}
        <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#43e97b;"></span>
                <span style="font-size:11px;color:var(--muted2);">Alta receita <span style="color:var(--muted);">(+70% do máximo)</span></span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#a855f7;"></span>
                <span style="font-size:11px;color:var(--muted2);">Receita média <span style="color:var(--muted);">(40–70%)</span></span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ec4899;"></span>
                <span style="font-size:11px;color:var(--muted2);">Baixa receita <span style="color:var(--muted);">(até 40%)</span></span>
            </div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:start;" class="map-grid">
        <div id="sales-map" style="height:380px;min-height:380px;border-radius:10px;overflow:hidden;border:1px solid var(--border);"></div>
        {{-- Ranking lateral --}}
        <div style="display:flex;flex-direction:column;gap:8px;max-height:380px;overflow-y:auto;">
            @php $maxCity = $sales_by_city->max('total') ?: 1; @endphp
            @forelse($sales_by_city->take(10) as $i => $city)
            <div style="padding:10px 12px;background:var(--surface2);border-radius:8px;border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;">
                        <span style="width:18px;height:18px;border-radius:50%;background:rgba(168,85,247,0.2);color:#a855f7;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;">{{ $i+1 }}</span>
                        {{ $city->city }}
                    </span>
                    <span style="font-size:11px;font-weight:700;color:#43e97b;">R$ {{ number_format($city->total, 0, ',', '.') }}</span>
                </div>
                <div style="background:var(--border);border-radius:4px;height:4px;overflow:hidden;">
                    <div style="height:100%;width:{{ round(($city->total / $maxCity) * 100) }}%;background:linear-gradient(90deg,#a855f7,#ec4899);border-radius:4px;"></div>
                </div>
                <div style="font-size:10px;color:var(--muted);margin-top:4px;">{{ $city->count }} {{ $city->count == 1 ? 'venda' : 'vendas' }}</div>
            </div>
            @empty
            <div style="text-align:center;padding:32px;color:var(--muted);font-size:13px;">Nenhuma venda com cidade preenchida</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Top 5 leads --}}
@if($top_leads->count())
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px;">Top 5 clientes por valor</div>
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($top_leads as $i => $tl)
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:24px;height:24px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#a855f7;flex-shrink:0;">
                {{ $i + 1 }}
            </div>
            <a href="{{ route('leads.show', $tl->id) }}" style="flex:1;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;">
                {{ $tl->phone }}
                @if($tl->city)<span style="font-size:11px;color:var(--muted);font-weight:400;"> · {{ $tl->city }}</span>@endif
            </a>
            <div style="font-size:12px;color:var(--muted);">{{ $tl->count }} {{ $tl->count === 1 ? 'venda' : 'vendas' }}</div>
            <div style="font-size:13px;font-weight:700;color:#43e97b;min-width:110px;text-align:right;">
                R$ {{ number_format($tl->total, 2, ',', '.') }}
            </div>
            <div style="width:80px;background:rgba(255,255,255,0.05);border-radius:4px;height:6px;flex-shrink:0;">
                <div style="height:100%;width:{{ $top_leads->first()->total > 0 ? round(($tl->total / $top_leads->first()->total) * 100) : 0 }}%;background:#a855f7;border-radius:4px;"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Tabela desktop --}}
<div class="sales-table-desktop card" style="padding:0;overflow:hidden;margin-bottom:16px;">
    <div style="overflow-x:auto;">
        <table style="min-width:600px;">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Produto</th>
                    <th>Valor</th>
                    <th>Observação</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr data-removable>
                    <td>
                        @if($sale->lead)
                        <a href="{{ route('leads.show', $sale->lead_id) }}" style="color:var(--accent);font-weight:500;text-decoration:none;display:flex;align-items:center;gap:8px;">
                            <div style="width:30px;height:30px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#a855f7;flex-shrink:0;">
                                {{ strtoupper(substr($sale->lead->phone, -2)) }}
                            </div>
                            {{ $sale->lead->phone }}
                        </a>
                        @else
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:30px;height:30px;border-radius:50%;background:rgba(100,100,100,0.15);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--muted);flex-shrink:0;">LD</div>
                            <span style="color:var(--muted);font-size:13px;">—</span>
                            <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:rgba(255,101,132,0.15);color:#ff6584;border:1px solid rgba(255,101,132,0.3);">Lead removido</span>
                        </div>
                        @endif
                    </td>
                    <td>
                        @if($sale->product)
                        <div style="font-size:13px;">{{ $sale->product->name }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $sale->product->category ?? '' }}</div>
                        @else
                        <span style="color:var(--muted);">—</span>
                        @endif
                    </td>
                    <td style="font-weight:700;color:#43e97b;">R$ {{ number_format($sale->value, 2, ',', '.') }}</td>
                    <td style="color:var(--muted);">{{ $sale->notes ?? '—' }}</td>
                    <td style="color:var(--muted);">{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <button type="button" class="btn btn-ghost" style="padding:5px 10px;font-size:12px;"
                                onclick="openEditModal('{{ $sale->id }}','{{ $sale->value }}','{{ Js::from($sale->notes ?? '') }}','{{ $sale->product_id ?? '' }}')">
                                <i data-lucide="pencil" style="width:12px;height:12px;"></i>
                            </button>
                            <button type="button" class="btn btn-danger" style="padding:5px 10px;font-size:12px;"
                                data-delete-url="{{ route('sales.destroy', $sale->id) }}"
                                onclick="confirmDelete(this,'Venda','{{ route('sales.destroy', $sale->id) }}','{{ route('sales.restore', $sale->id) }}')">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma venda encontrada</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sales->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface) 0%,var(--surface2) 100%);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            💰 Mostrando <strong style="color:var(--accent);">{{ $sales->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $sales->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $sales->total() }}</strong> registros
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if($sales->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $sales->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">← Anterior</a>
            @endif

            @php
                $current = $sales->currentPage();
                $last    = $sales->lastPage();
                $from    = max(1, $current - 2);
                $to      = min($last, $current + 2);
                if ($to - $from < 4 && $last > 4) {
                    if ($from === 1) { $to = min($last, 5); }
                    elseif ($to === $last) { $from = max(1, $last - 4); }
                }
            @endphp
            <div style="display:flex;gap:4px;align-items:center;">
                @if($from > 1)
                    <a href="{{ $sales->url(1) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">1</a>
                    @if($from > 2)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                @endif
                @for($page = $from; $page <= $to; $page++)
                    @if($page == $current)
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">{{ $page }}</span>
                    @else
                        <a href="{{ $sales->url($page) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $page }}</a>
                    @endif
                @endfor
                @if($to < $last)
                    @if($to < $last - 1)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                    <a href="{{ $sales->url($last) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $last }}</a>
                @endif
            </div>

            @if($sales->hasMorePages())
                <a href="{{ $sales->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">Próxima →</a>
            @else
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Cards mobile --}}
<div class="sales-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($sales as $sale)
    <div class="card" data-removable style="padding:14px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            @if($sale->lead)
            <a href="{{ route('leads.show', $sale->lead_id) }}" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#a855f7;flex-shrink:0;">
                    {{ strtoupper(substr($sale->lead->phone, -2)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--text);">{{ $sale->lead->phone }}</div>
                    <div style="font-size:11px;color:var(--muted);">{{ $sale->product->name ?? '—' }} · {{ $sale->sold_at->format('d/m/Y H:i') }}</div>
                </div>
            </a>
            @else
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(100,100,100,0.15);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--muted);flex-shrink:0;">LD</div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--muted);">—
                        <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:rgba(255,101,132,0.15);color:#ff6584;border:1px solid rgba(255,101,132,0.3);margin-left:4px;">Lead removido</span>
                    </div>
                    <div style="font-size:11px;color:var(--muted);">{{ $sale->product->name ?? '—' }} · {{ $sale->sold_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            @endif
            <div style="font-size:16px;font-weight:700;color:#43e97b;">R$ {{ number_format($sale->value, 2, ',', '.') }}</div>
        </div>
        @if($sale->notes)
        <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">{{ $sale->notes }}</div>
        @endif
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px;padding:7px;"
                onclick="openEditModal('{{ $sale->id }}','{{ $sale->value }}','{{ Js::from($sale->notes ?? '') }}','{{ $sale->product_id ?? '' }}')">
                <i data-lucide="pencil" style="width:13px;height:13px;"></i> Editar
            </button>
            <button type="button" class="btn btn-danger" style="padding:7px 12px;font-size:12px;"
                data-delete-url="{{ route('sales.destroy', $sale->id) }}"
                onclick="confirmDelete(this,'Venda','{{ route('sales.destroy', $sale->id) }}','{{ route('sales.restore', $sale->id) }}')">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
            </button>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma venda encontrada</div>
    @endforelse

    @if($sales->hasPages())
    <div style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;color:var(--muted);">{{ $sales->firstItem() }}–{{ $sales->lastItem() }} de {{ $sales->total() }}</div>
        <div style="display:flex;gap:8px;">
            @if($sales->onFirstPage())
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $sales->previousPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
            @endif
            @if($sales->hasMorePages())
                <a href="{{ $sales->nextPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
            @else
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Modal editar --}}
<div id="modal-edit-sale" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:420px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Editar Venda</h3>
            <button onclick="document.getElementById('modal-edit-sale').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" id="form-edit-sale" action="">
            @csrf @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>Valor da venda (R$) *</label>
                <input type="number" name="value" id="edit-sale-value" class="input" step="0.01" min="0.01" required>
            </div>
            <div style="margin-bottom:14px;">
                <label>Produto</label>
                <select name="product_id" id="edit-sale-product" class="input">
                    <option value="">— Nenhum —</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label>Observação</label>
                <input type="text" name="notes" id="edit-sale-notes" class="input" placeholder="Ex: iPhone 15 Pro 256GB">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Salvar alterações
            </button>
        </form>
    </div>
</div>

<style>
.sales-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.sales-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.sales-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}
.sales-table-desktop { display: block; }
.sales-cards-mobile  { display: none;  }
@media (max-width: 1024px) {
    .sales-summary { grid-template-columns: repeat(2, 1fr); }
    .charts-row    { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .sales-page-header        { flex-direction: column; gap: 10px; }
    .btn-export-sales         { width: 100%; justify-content: center; }
    .sales-filters            { gap: 6px; }
    .sales-filters .filter-btn{ flex: 1; text-align: center; min-width: 0; }
    .sales-summary            { grid-template-columns: 1fr 1fr; gap: 10px; }
    .sales-table-desktop      { display: none !important; }
    .sales-cards-mobile       { display: flex !important; }
}
.map-grid { grid-template-columns: 1fr 280px; }
@media (max-width: 900px) {
    .map-grid { grid-template-columns: 1fr !important; }
    #sales-map { height: 280px !important; }
}
@media (max-width: 480px) {
    .sales-summary { grid-template-columns: 1fr 1fr; gap: 8px; }
    .sales-summary .card { padding: 12px 14px; }
    .sales-summary .card-value { font-size: 16px !important; }
}
</style>

@push('scripts')
<script>
const salesPerDay    = @json($sales_per_day);
const byProduct      = @json($sales_by_product);
const byCategory     = @json($sales_by_category);
const bySource       = @json($sales_by_source);
const byHour         = @json($sales_by_hour);

const gridColor  = 'rgba(100,100,150,0.15)';
const tickColor  = '#6b6b90';
const tickFont   = { size: 11 };

new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: salesPerDay.map(d => { const [y,m,day] = d.day.split('-'); return day+'/'+m; }),
        datasets: [
            {
                label: 'Valor (R$)',
                data: salesPerDay.map(d => d.total),
                backgroundColor: 'rgba(168,85,247,0.5)',
                borderColor: '#a855f7',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Qtd vendas',
                data: salesPerDay.map(d => d.count),
                type: 'line',
                borderColor: '#43e97b',
                backgroundColor: 'rgba(67,233,123,0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#43e97b',
                yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: tickColor, font: tickFont } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2})
                        : ' ' + ctx.parsed.y + ' vendas'
                }
            }
        },
        scales: {
            x:  { grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont } },
            y:  { position: 'left',  grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont, callback: v => 'R$ ' + v.toLocaleString('pt-BR') }, beginAtZero: true },
            y2: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#43e97b', font: tickFont }, beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('productChart'), {
    type: 'bar',
    data: {
        labels: byProduct.map(p => p.name.length > 18 ? p.name.substring(0,18)+'…' : p.name),
        datasets: [{
            label: 'Receita (R$)',
            data: byProduct.map(p => p.total),
            backgroundColor: [
                'rgba(168,85,247,0.7)','rgba(67,233,123,0.7)','rgba(255,193,7,0.7)',
                'rgba(0,210,255,0.7)','rgba(255,101,132,0.7)','rgba(255,152,0,0.7)',
                'rgba(100,181,246,0.7)','rgba(77,208,225,0.7)'
            ],
            borderRadius: 4,
            borderWidth: 0,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' R$ ' + ctx.parsed.x.toLocaleString('pt-BR', {minimumFractionDigits:2}) + ' · ' + (byProduct[ctx.dataIndex]?.count ?? 0) + ' vendas' } }
        },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont, callback: v => 'R$ ' + v.toLocaleString('pt-BR') }, beginAtZero: true },
            y: { grid: { display: false }, ticks: { color: tickColor, font: tickFont } }
        }
    }
});

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: byCategory.map(c => c.category),
        datasets: [{
            data: byCategory.map(c => c.total),
            backgroundColor: [
                'rgba(168,85,247,0.8)','rgba(67,233,123,0.8)','rgba(255,193,7,0.8)',
                'rgba(0,210,255,0.8)','rgba(255,101,132,0.8)','rgba(255,152,0,0.8)',
                'rgba(100,181,246,0.8)','rgba(77,208,225,0.8)'
            ],
            borderWidth: 2,
            borderColor: 'rgba(0,0,0,0.2)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { color: tickColor, font: tickFont, padding: 12, boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ' R$ ' + ctx.parsed.toLocaleString('pt-BR', {minimumFractionDigits:2}) } }
        },
        cutout: '60%',
    }
});

new Chart(document.getElementById('sourceChart'), {
    type: 'bar',
    data: {
        labels: bySource.map(s => s.source ?? 'Desconhecida'),
        datasets: [
            {
                label: 'Receita (R$)',
                data: bySource.map(s => s.total),
                backgroundColor: 'rgba(0,210,255,0.6)',
                borderColor: '#00d2ff',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Qtd',
                data: bySource.map(s => s.count),
                type: 'line',
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#ffc107',
                yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { labels: { color: tickColor, font: tickFont } } },
        scales: {
            x:  { grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont } },
            y:  { position: 'left',  grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont, callback: v => 'R$ ' + v.toLocaleString('pt-BR') }, beginAtZero: true },
            y2: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#ffc107', font: tickFont }, beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
        labels: byHour.map(h => h.hour),
        datasets: [{
            label: 'Vendas',
            data: byHour.map(h => h.count),
            backgroundColor: byHour.map(h => {
                const max = Math.max(...byHour.map(x => x.count));
                return h.count === max ? 'rgba(255,101,132,0.8)' : 'rgba(255,101,132,0.35)';
            }),
            borderRadius: 4,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' vendas' } }
        },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont } },
            y: { grid: { color: gridColor }, ticks: { color: tickColor, font: tickFont }, beginAtZero: true }
        }
    }
});

function openEditModal(id, value, notes, productId) {
    document.getElementById('edit-sale-value').value   = value;
    document.getElementById('edit-sale-notes').value   = notes;
    document.getElementById('edit-sale-product').value = productId || '';
    document.getElementById('form-edit-sale').action   = '/sales/' + id;
    document.getElementById('modal-edit-sale').style.display = 'flex';
    lucide.createIcons();
}
</script>
<script>
// ── Mapa de vendas por cidade ─────────────────────────────────────────────
const salesByCity = @json($sales_by_city);

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
    if (!L) { console.error('Leaflet não carregou'); return; }

    if (salesByCity.length > 0) {
        const map = L.map('sales-map', { zoomControl: true, scrollWheelZoom: false })
            .setView([-15.7801, -47.9292], 4);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        setTimeout(() => map.invalidateSize(), 300);
        setTimeout(() => map.invalidateSize(), 800);

        const maxVal = Math.max(...salesByCity.map(c => c.total));

        for (const city of salesByCity) {
            const coords = await getCoordsForCity(city.city);
            if (!coords) continue;

            const pct   = city.total / maxVal;
            const color = pct > 0.7 ? '#43e97b' : pct > 0.4 ? '#a855f7' : '#ec4899';

            L.circleMarker(coords, {
                radius: 10 + pct * 35, fillColor: color, color, weight: 1.5,
                opacity: 0.9, fillOpacity: 0.5 + (pct * 0.3),
            }).addTo(map).bindPopup(
                `<div style="font-family:Inter,sans-serif;font-size:13px;min-width:160px;">
                    <strong>${city.city}</strong><br>
                    <span style="color:#43e97b;font-weight:700;">R$ ${parseFloat(city.total).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span><br>
                    <span style="color:#888;">${city.count} ${city.count==1?'venda':'vendas'}</span>
                </div>`
            );
        }
    } else {
        document.getElementById('sales-map').innerHTML =
            '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#6b6b90;font-size:13px;">Nenhuma venda com cidade preenchida</div>';
    }
});
</script>

@endpush

@endsection