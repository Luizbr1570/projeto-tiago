@extends('layouts.app')
@section('title', 'Lead · ' . $lead->phone)

@section('content')

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:13px;color:var(--muted);">
    <a href="{{ route('leads.index') }}" style="color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:4px;">
        <i data-lucide="arrow-left" style="width:13px;height:13px;"></i> Leads
    </a>
    <span>/</span>
    <span style="color:var(--text);">{{ $lead->phone }}</span>
</div>

<div class="lead-show-grid" style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start;">

    {{-- Coluna esquerda --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Info card --}}
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:14px;font-weight:600;">Informações</span>
                <span class="badge badge-{{ $lead->status }}">{{ str_replace('_',' ',$lead->status) }}</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach([
                    ['TELEFONE', $lead->phone, 'phone'],
                    ['CIDADE',   $lead->city   ?? '—', 'map-pin'],
                    ['ORIGEM',   $lead->source ?? '—', 'share-2'],
                    ['CRIADO EM',$lead->created_at->format('d/m/Y H:i'), 'calendar'],
                ] as [$l, $v, $icon])
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:28px;height:28px;border-radius:6px;background:var(--surface2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="{{ $icon }}" style="width:12px;height:12px;color:var(--muted);"></i>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--muted);font-weight:600;">{{ $l }}</div>
                        <div style="font-size:13px;">{{ $v }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Status card --}}
        <div class="card">
            <div style="font-size:13px;font-weight:600;margin-bottom:14px;">Atualizar status</div>
            <form method="POST" action="{{ route('leads.update', $lead->id) }}">
                @csrf @method('PATCH')
                <select name="status" class="input" style="margin-bottom:10px;">
                    @foreach(['novo','em_conversa','pediu_preco','encaminhado','perdido','recuperacao'] as $s)
                    <option value="{{ $s }}" {{ $lead->status===$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i data-lucide="save" style="width:13px;height:13px;"></i> Salvar
                </button>
            </form>
        </div>

        {{-- Vendas card --}}
        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div style="font-size:13px;font-weight:600;">Vendas</div>
                <button onclick="document.getElementById('modal-sale').style.display='flex'" class="btn btn-primary" style="padding:5px 10px;font-size:12px;">
                    <i data-lucide="plus" style="width:12px;height:12px;"></i> Registrar
                </button>
            </div>
            @forelse($lead->sales->sortByDesc('sold_at') as $sale)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#43e97b;">R$ {{ number_format($sale->value, 2, ',', '.') }}</div>
                    @if($sale->notes)
                    <div style="font-size:11px;color:var(--muted);">{{ $sale->notes }}</div>
                    @endif
                    <div style="font-size:10px;color:var(--muted);">{{ $sale->sold_at->format('d/m/Y H:i') }}</div>
                </div>
                <form method="POST" action="{{ route('sales.destroy', $sale->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="padding:4px 8px;">
                        <i data-lucide="trash-2" style="width:11px;height:11px;"></i>
                    </button>
                </form>
            </div>
            @empty
            <div style="text-align:center;padding:16px 0;color:var(--muted);font-size:12px;">Nenhuma venda registrada</div>
            @endforelse
            @if($lead->sales->count() > 0)
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:var(--muted);font-weight:600;">TOTAL</span>
                <span style="font-size:14px;font-weight:700;color:#43e97b;">R$ {{ number_format($lead->sales->sum('value'), 2, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Histórico de conversas --}}
    <div class="card" style="min-height:400px;">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i data-lucide="message-circle" style="width:15px;height:15px;color:var(--accent);"></i>
            Histórico de conversas
            <span style="font-size:11px;color:var(--muted);font-weight:400;margin-left:auto;">{{ $conversations->total() }} mensagens</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;max-height:560px;overflow-y:auto;padding-right:4px;">
            @forelse($conversations as $conv)
            <div style="display:flex;gap:10px;{{ $conv->sender==='human'?'flex-direction:row-reverse;':'' }}">
                <div style="width:28px;height:28px;border-radius:50%;background:{!! \App\Helpers\ConversationHelper::senderColor($conv->sender) !!};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="{{ \App\Helpers\ConversationHelper::senderIcon($conv->sender) }}" style="width:12px;height:12px;color:{{ \App\Helpers\ConversationHelper::senderIconColor($conv->sender) }};"></i>
                </div>
                <div style="max-width:75%;">
                    <div style="font-size:10px;color:var(--muted);margin-bottom:3px;{{ $conv->sender==='human'?'text-align:right;':'' }}">
                        {{ ucfirst($conv->sender) }} · {{ $conv->created_at->format('d/m H:i') }}
                    </div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:9px 13px;font-size:13px;line-height:1.5;word-break:break-word;">
                        {{ $conv->message }}
                    </div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:40px 0;">
                <i data-lucide="message-circle" style="width:32px;height:32px;color:var(--border);margin-bottom:10px;"></i>
                <p style="color:var(--muted);font-size:13px;">Nenhuma conversa ainda</p>
            </div>
            @endforelse
        </div>

        {{-- Paginação das conversas --}}
        @if($conversations->hasPages())
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <div style="font-size:12px;color:var(--muted);">
                {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }} de {{ $conversations->total() }} mensagens
            </div>
            <div style="display:flex;gap:6px;">
                @if($conversations->onFirstPage())
                    <span style="padding:6px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">← Anterior</span>
                @else
                    <a href="{{ $conversations->previousPageUrl() }}" style="padding:6px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
                @endif
                @if($conversations->hasMorePages())
                    <a href="{{ $conversations->nextPageUrl() }}" style="padding:6px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
                @else
                    <span style="padding:6px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">Próxima →</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal registrar venda --}}
<div id="modal-sale" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:400px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Registrar Venda</h3>
            <button onclick="document.getElementById('modal-sale').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('sales.store', $lead->id) }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label>Valor da venda (R$) *</label>
                <input type="number" name="value" class="input" placeholder="5000.00" step="0.01" min="0.01" required>
            </div>
            <div style="margin-bottom:20px;">
                <label>Observação</label>
                <input type="text" name="notes" class="input" placeholder="Ex: iPhone 15 Pro 256GB">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="check" style="width:14px;height:14px;"></i> Confirmar venda
            </button>
        </form>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .lead-show-grid {
        grid-template-columns: 1fr !important;
    }
    .lead-show-grid > div:first-child {
        /* Info e status lado a lado no mobile */
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
}
@media (max-width: 480px) {
    .lead-show-grid > div:first-child {
        grid-template-columns: 1fr !important;
    }
}
</style>

@endsection