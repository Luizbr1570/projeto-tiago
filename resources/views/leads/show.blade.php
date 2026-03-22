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
                    <div style="font-size:11px;color:var(--accent);font-weight:500;">
                        @if($sale->product){{ $sale->product->name }} @endif<span style="color:var(--muted);">× {{ $sale->quantity ?? 1 }}</span>
                    </div>
                    @if($sale->notes)
                    <div style="font-size:11px;color:var(--muted);">{{ $sale->notes }}</div>
                    @endif
                    <div style="font-size:10px;color:var(--muted);">{{ $sale->sold_at->format('d/m/Y H:i') }}</div>
                </div>
                {{-- Botões editar e deletar --}}
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="button" class="btn btn-ghost" style="padding:4px 8px;"
                        onclick="openEditSale('{{ $sale->id }}','{{ $sale->value }}',{{ Js::from($sale->notes ?? '') }},'{{ $sale->product_id ?? '' }}','{{ $sale->quantity ?? 1 }}')">
                        <i data-lucide="pencil" style="width:11px;height:11px;"></i>
                    </button>
                    <button type="button" class="btn btn-danger" style="padding:4px 8px;"
                        data-delete-url="{{ route('sales.destroy', $sale->id) }}"
                        onclick="confirmDelete(this,'Venda','{{ route('sales.destroy', $sale->id) }}','{{ route('sales.restore', $sale->id) }}')">
                        <i data-lucide="trash-2" style="width:11px;height:11px;"></i>
                    </button>
                </div>
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
    <div class="card" style="width:100%;max-width:420px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Registrar Venda</h3>
            <button onclick="document.getElementById('modal-sale').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('sales.store', $lead->id) }}">
            @csrf

            <div style="margin-bottom:14px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">PRODUTO</label>
                <select name="product_id" id="product-select" class="input">
                    <option value="" data-price="">— Selecionar produto (opcional) —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->avg_price ?? '' }}">
                            {{ $product->name }}
                            @if($product->avg_price)
                                — R$ {{ number_format($product->avg_price, 2, ',', '.') }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">QUANTIDADE</label>
                <div style="display:flex;align-items:center;width:100%;">
                    <button type="button" onclick="changeQty(-1)" style="width:44px;height:42px;background:var(--surface2);border:1px solid var(--border);border-right:none;border-radius:8px 0 0 8px;color:var(--text);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">−</button>
                    <input type="number" name="quantity" id="qty-input" class="input"
                           value="1" min="1" max="9999"
                           style="margin-bottom:0;border-radius:0;text-align:center;border-left:none;border-right:none;flex:1;min-width:0;font-size:15px;font-weight:600;">
                    <button type="button" onclick="changeQty(1)" style="width:44px;height:42px;background:var(--surface2);border:1px solid var(--border);border-left:none;border-radius:0 8px 8px 0;color:var(--text);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">+</button>
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">VALOR TOTAL (R$) *</label>
                <input type="number" name="value" id="sale-value" class="input"
                       placeholder="0,00" step="0.01" min="0.01" required
                       style="margin-bottom:0;">
            </div>

            <div id="price-hint" style="display:none;font-size:11px;color:var(--muted);margin-bottom:14px;padding:7px 10px;background:var(--surface2);border-radius:6px;">
                <span id="price-hint-text"></span>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">OBSERVAÇÃO</label>
                <input type="text" name="notes" class="input" placeholder="Ex: iPhone 15 Pro 256GB preto" style="margin-bottom:0;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="check" style="width:14px;height:14px;"></i> Confirmar venda
            </button>
        </form>
    </div>
</div>

{{-- Modal editar venda --}}
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
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">PRODUTO</label>
                <select name="product_id" id="edit-sale-product" class="input">
                    <option value="" data-price="">— Nenhum —</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->avg_price ?? '' }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">QUANTIDADE</label>
                <div style="display:flex;align-items:center;width:100%;">
                    <button type="button" onclick="changeEditQty(-1)" style="width:44px;height:42px;background:var(--surface2);border:1px solid var(--border);border-right:none;border-radius:8px 0 0 8px;color:var(--text);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">−</button>
                    <input type="number" name="quantity" id="edit-qty-input" class="input"
                           value="1" min="1" max="9999"
                           style="margin-bottom:0;border-radius:0;text-align:center;border-left:none;border-right:none;flex:1;min-width:0;font-size:15px;font-weight:600;">
                    <button type="button" onclick="changeEditQty(1)" style="width:44px;height:42px;background:var(--surface2);border:1px solid var(--border);border-left:none;border-radius:0 8px 8px 0;color:var(--text);font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">+</button>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">VALOR TOTAL (R$) *</label>
                <input type="number" name="value" id="edit-sale-value" class="input" step="0.01" min="0.01" required>
            </div>
            <div id="edit-price-hint" style="display:none;font-size:11px;color:var(--muted);margin-bottom:14px;padding:7px 10px;background:var(--surface2);border-radius:6px;">
                <span id="edit-price-hint-text"></span>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;color:var(--muted);font-weight:600;display:block;margin-bottom:6px;">OBSERVAÇÃO</label>
                <input type="text" name="notes" id="edit-sale-notes" class="input" placeholder="Ex: iPhone 15 Pro 256GB">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Salvar alterações
            </button>
        </form>
    </div>
</div>

<script>
    let currentPrice = 0;

    function changeQty(delta) {
        const input = document.getElementById('qty-input');
        input.value = Math.max(1, Math.min(9999, parseInt(input.value || 1) + delta));
        recalcTotal();
    }

    document.getElementById('qty-input').addEventListener('input', recalcTotal);

    document.getElementById('product-select').addEventListener('change', function () {
        currentPrice = parseFloat(this.options[this.selectedIndex].dataset.price) || 0;
        recalcTotal();
    });

    function recalcTotal() {
        const qty        = parseInt(document.getElementById('qty-input').value) || 1;
        const hint       = document.getElementById('price-hint');
        const hintText   = document.getElementById('price-hint-text');
        const valueInput = document.getElementById('sale-value');

        if (currentPrice > 0) {
            const total = (currentPrice * qty).toFixed(2);
            valueInput.value = total;
            hintText.textContent = qty + ' × R$ ' + currentPrice.toLocaleString('pt-BR', {minimumFractionDigits:2}) + ' = R$ ' + parseFloat(total).toLocaleString('pt-BR', {minimumFractionDigits:2});
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    // ── Modal editar venda ────────────────────────────────────────────────
    let editCurrentPrice = 0;

    function changeEditQty(delta) {
        const input = document.getElementById('edit-qty-input');
        input.value = Math.max(1, Math.min(9999, parseInt(input.value || 1) + delta));
        recalcEditTotal();
    }

    document.getElementById('edit-qty-input').addEventListener('input', recalcEditTotal);

    document.getElementById('edit-sale-product').addEventListener('change', function () {
        editCurrentPrice = parseFloat(this.options[this.selectedIndex].dataset.price) || 0;
        recalcEditTotal();
    });

    function recalcEditTotal() {
        const qty      = parseInt(document.getElementById('edit-qty-input').value) || 1;
        const hint     = document.getElementById('edit-price-hint');
        const hintText = document.getElementById('edit-price-hint-text');
        const valInput = document.getElementById('edit-sale-value');

        if (editCurrentPrice > 0) {
            const total = (editCurrentPrice * qty).toFixed(2);
            valInput.value = total;
            hintText.textContent = qty + ' × R$ ' + editCurrentPrice.toLocaleString('pt-BR', {minimumFractionDigits:2}) + ' = R$ ' + parseFloat(total).toLocaleString('pt-BR', {minimumFractionDigits:2});
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    function openEditSale(id, value, notes, productId, quantity) {
        // Seta os campos
        document.getElementById('edit-sale-notes').value   = notes;
        document.getElementById('edit-qty-input').value    = quantity || 1;
        document.getElementById('form-edit-sale').action   = '/sales/' + id;

        // Seta o produto e busca o preço automaticamente
        const productSelect = document.getElementById('edit-sale-product');
        productSelect.value = productId || '';

        // Pega o preço do produto selecionado
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        editCurrentPrice = parseFloat(selectedOption?.dataset?.price) || 0;

        if (editCurrentPrice > 0) {
            // Recalcula automaticamente com a quantidade carregada
            recalcEditTotal();
        } else {
            // Sem produto com preço, mantém o valor original da venda
            document.getElementById('edit-sale-value').value = value;
            document.getElementById('edit-price-hint').style.display = 'none';
        }

        document.getElementById('modal-edit-sale').style.display = 'flex';
        lucide.createIcons();
    }
</script>

<style>
@media (max-width: 768px) {
    .lead-show-grid { grid-template-columns: 1fr !important; }
    .lead-show-grid > div:first-child { display: grid !important; grid-template-columns: 1fr 1fr; gap: 12px; }
}
@media (max-width: 480px) {
    .lead-show-grid > div:first-child { grid-template-columns: 1fr !important; }
}
</style>

@endsection