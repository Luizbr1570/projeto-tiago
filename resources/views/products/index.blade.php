@extends('layouts.app')
@section('title', 'Produtos')

@section('content')
<div class="page-header">
    <h1>Produtos — {{ auth()->user()->company->name }}</h1>
    <p>O que os clientes mais procuram e quanto vale cada conversa</p>
</div>

{{-- ── Produtos mais buscados + Ticket médio ── --}}
<div class="prod-grid-top">

    {{-- Barra horizontal --}}
    <div class="card card-scrollable">
        <div class="card-title">Produtos mais buscados</div>
        <div class="bars-scroll">
        @php $maxBusca = $products->max('interests_count') ?: 1; @endphp
        @forelse($products as $product)
        <div class="bar-item">
            <div class="bar-label">
                <span class="bar-name">{{ $product->name }}</span>
                <span class="bar-count">{{ $product->interests_count }}</span>
            </div>
            <div class="bar-track">
                @php $pct = round(($product->interests_count / $maxBusca) * 100); @endphp
                <div class="bar-fill" style="width:{{ $pct }}%;"></div>
            </div>
        </div>
        @empty
        <div class="empty-state">Nenhum produto com interesse registrado</div>
        @endforelse
        </div>{{-- /bars-scroll --}}
    </div>

    {{-- Ticket médio por produto --}}
    <div class="card card-flush">
        <div class="card-header-inner">
            <div class="card-title">Ticket médio por produto</div>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="th-left">Produto</th>
                        <th class="th-center">Vendas</th>
                        <th class="th-right">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td class="td-name">{{ $product->name }}</td>
                        <td class="td-center">
                            @if(($product->sales_count ?? 0) > 0)
                                <span class="badge badge-green">{{ $product->sales_count }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="td-right td-accent">R$ {{ number_format($product->avg_price ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="td-empty">Nenhum produto cadastrado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Tabela interações por produto ── --}}
<div class="card card-flush">
    <div class="section-header">
        <div class="card-title">Interações por produto</div>
        <div class="section-actions">
            <select id="filter-category" class="input select-compact" onchange="filterByCategory(this.value)">
                <option value="">Todas as categorias</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            <button onclick="document.getElementById('modal-product').style.display='flex'" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="btn-icon"></i>
                <span>Novo produto</span>
            </button>
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="table-scroll desktop-only">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="th-left">Produto</th>
                    <th class="th-center">Buscas</th>
                    <th class="th-center">Vendas</th>
                    <th class="th-right">Ticket médio</th>
                    <th class="th-center">Ação</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                @forelse($products as $product)
                <tr data-removable data-category="{{ $product->category ?? '' }}" class="table-row">
                    <td class="td-product">
                        <div class="product-cell">
                            <div class="product-avatar">{{ strtoupper(substr($product->name, 0, 1)) }}</div>
                            <div>
                                <div class="product-name">{{ $product->name }}</div>
                                @if($product->category)
                                <div class="product-category">{{ $product->category }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="td-center td-bold">{{ $product->interests_count ?? 0 }}</td>
                    <td class="td-center">
                        @if(($product->sales_count ?? 0) > 0)
                            <span class="badge badge-green">{{ $product->sales_count }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="td-right td-accent">R$ {{ number_format($product->avg_price ?? 0, 2, ',', '.') }}</td>
                    <td class="td-center">
                        <button type="button"
                            class="btn btn-danger btn-icon-only"
                            title="Remover produto"
                            data-delete-url="{{ route('products.destroy', $product->id) }}"
                            onclick="confirmDelete(this,'Produto','{{ route('products.destroy', $product->id) }}','{{ route('products.restore', $product->id) }}')">
                            <i data-lucide="trash-2" class="icon-sm"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="td-empty">
                        Nenhum produto cadastrado.
                        <a href="#" onclick="document.getElementById('modal-product').style.display='flex';return false" class="link-accent">Crie o primeiro!</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div id="no-filter-results" style="display:none;" class="empty-state">
            Nenhum produto encontrado nessa categoria.
        </div>
    </div>

    {{-- Mobile cards list --}}
    <div class="mobile-only" id="products-mobile">
        @forelse($products as $product)
        <div class="product-card-mobile" data-removable data-category="{{ $product->category ?? '' }}">
            <div class="pcm-header">
                <div class="product-cell">
                    <div class="product-avatar">{{ strtoupper(substr($product->name, 0, 1)) }}</div>
                    <div>
                        <div class="product-name">{{ $product->name }}</div>
                        @if($product->category)
                        <div class="product-category">{{ $product->category }}</div>
                        @endif
                    </div>
                </div>
                <button type="button"
                    class="btn btn-danger btn-icon-only"
                    title="Remover produto"
                    data-delete-url="{{ route('products.destroy', $product->id) }}"
                    onclick="confirmDelete(this,'Produto','{{ route('products.destroy', $product->id) }}','{{ route('products.restore', $product->id) }}')">
                    <i data-lucide="trash-2" class="icon-sm"></i>
                </button>
            </div>
            <div class="pcm-stats">
                <div class="pcm-stat">
                    <span class="pcm-label">Buscas</span>
                    <span class="pcm-value">{{ $product->interests_count ?? 0 }}</span>
                </div>
                <div class="pcm-stat">
                    <span class="pcm-label">Vendas</span>
                    <span class="pcm-value">
                        @if(($product->sales_count ?? 0) > 0)
                            <span class="badge badge-green">{{ $product->sales_count }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </span>
                </div>
                <div class="pcm-stat">
                    <span class="pcm-label">Ticket médio</span>
                    <span class="pcm-value td-accent">R$ {{ number_format($product->avg_price ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state" style="padding:32px 16px;">
            Nenhum produto cadastrado.
            <a href="#" onclick="document.getElementById('modal-product').style.display='flex';return false" class="link-accent">Crie o primeiro!</a>
        </div>
        @endforelse
        <div id="no-filter-results-mobile" style="display:none;" class="empty-state" style="padding:32px 16px;">
            Nenhum produto encontrado nessa categoria.
        </div>
    </div>
</div>

{{-- Modal novo produto --}}
<div id="modal-product" class="modal-backdrop" onclick="if(event.target===this)this.style.display='none'">
    <div class="card modal-card">
        <div class="modal-header">
            <h3 class="modal-title">Novo Produto</h3>
            <button onclick="document.getElementById('modal-product').style.display='none'" class="modal-close">
                <i data-lucide="x" style="width:18px;height:18px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nome do produto *</label>
                <input type="text" name="name" class="input" placeholder="iPhone 15 Pro Max" required>
            </div>
            <div class="modal-row">
                <div>
                    <label class="form-label">Categoria</label>
                    <select id="category-select" class="input" style="margin-bottom:0;" onchange="handleCategoryChange(this)">
                        <option value="">— Selecionar —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                        <option value="__nova__">+ Nova categoria...</option>
                    </select>
                    <input type="text" name="category" id="category-input" class="input"
                           placeholder="Digite a categoria"
                           style="margin-bottom:0;margin-top:8px;display:none;">
                </div>
                <div>
                    <label class="form-label">Preço médio (R$)</label>
                    <input type="number" name="avg_price" class="input" placeholder="6420" step="0.01">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <i data-lucide="plus" style="width:14px;height:14px;"></i> Cadastrar Produto
            </button>
        </form>
    </div>
</div>

<script>
function filterByCategory(category) {
    // Desktop table rows
    const rows = document.querySelectorAll('#products-tbody tr[data-category]');
    let visibleDesktop = 0;
    rows.forEach(row => {
        const match = !category || row.dataset.category === category;
        row.style.display = match ? '' : 'none';
        if (match) visibleDesktop++;
    });
    const noResultsDesktop = document.getElementById('no-filter-results');
    if (noResultsDesktop) noResultsDesktop.style.display = visibleDesktop === 0 ? 'block' : 'none';

    // Mobile cards
    const cards = document.querySelectorAll('#products-mobile .product-card-mobile[data-category]');
    let visibleMobile = 0;
    cards.forEach(card => {
        const match = !category || card.dataset.category === category;
        card.style.display = match ? '' : 'none';
        if (match) visibleMobile++;
    });
    const noResultsMobile = document.getElementById('no-filter-results-mobile');
    if (noResultsMobile) noResultsMobile.style.display = visibleMobile === 0 ? 'block' : 'none';
}

function handleCategoryChange(select) {
    const input = document.getElementById('category-input');
    if (select.value === '__nova__') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
        select.value = '';
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = select.value;
    }
}
</script>

<style>
/* ── Layout ── */
.prod-grid-top {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

/* ── Cards ── */
.card-flush { padding: 0; overflow: hidden; }
.card-title { font-size: 14px; font-weight: 600; margin-bottom: 18px; }
.card-header-inner {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
}
.section-header {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.section-header .card-title { margin-bottom: 0; }
.section-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── Buttons ── */
.btn-sm { font-size: 12px; padding: 7px 14px; }
.btn-icon { width: 14px; height: 14px; margin-right: 6px; vertical-align: middle; }
.btn-icon-only { padding: 6px 10px; font-size: 11px; }
.btn-full {
    width: 100%; padding: 12px;
    font-size: 13px; font-weight: 600;
    border-radius: 6px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.select-compact { margin-bottom: 0; font-size: 12px; padding: 7px 12px; width: auto; }

/* ── Bar chart ── */
.card-scrollable { display: flex; flex-direction: column; }
.bars-scroll { overflow-y: auto; max-height: 320px; padding-right: 4px; }
.bars-scroll::-webkit-scrollbar { width: 4px; }
.bars-scroll::-webkit-scrollbar-track { background: transparent; }
.bars-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
.bar-item { margin-bottom: 7px; }
.bar-label {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 3px;
}
.bar-name { font-size: 11px; color: var(--muted2); }
.bar-count { font-size: 11px; font-weight: 600; }
.bar-track { background: var(--surface2); border-radius: 3px; height: 6px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #a855f7, #ec4899); border-radius: 3px; }

/* ── Tables ── */
.table-scroll { overflow-x: auto; }
.data-table { width: 100%; font-size: 10px; border-collapse: collapse; }
.data-table thead { background: var(--surface2); }
.th-left   { padding: 5px 7px; text-align: left; font-size: 10px; white-space: nowrap; }
.th-center { padding: 5px 7px; text-align: center; font-size: 10px; white-space: nowrap; }
.th-right  { padding: 5px 7px; text-align: right; font-size: 10px; white-space: nowrap; }
.td-name   { padding: 4px 7px; font-weight: 500; }
.td-center { padding: 4px 7px; text-align: center; }
.td-right  { padding: 4px 7px; text-align: right; }
.td-bold   { font-weight: 600; }
.td-accent { color: var(--accent); font-weight: 600; font-size: 10px; }
.td-empty  { text-align: center; padding: 14px; color: var(--muted); }
.td-product { padding: 4px 7px; }
.table-row { border-bottom: 1px solid var(--border); transition: background 0.2s; }
.table-row:hover { background: var(--surface2); }
.data-table tbody tr { border-bottom: 1px solid var(--border); }

/* ── Product cell ── */
.product-cell { display: flex; align-items: center; gap: 6px; }
.product-avatar {
    width: 20px; height: 20px; border-radius: 4px;
    background: rgba(168,85,247,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; font-weight: 700; color: #a855f7; flex-shrink: 0;
}
.product-name  { font-weight: 500; font-size: 11px; line-height: 1.2; }
.product-category { font-size: 9px; color: var(--muted); margin-top: 1px; }

/* ── Badges / misc ── */
.badge { font-weight: 700; font-size: 10px; padding: 2px 6px; border-radius: 20px; }
.badge-green { background: rgba(67,233,123,0.12); color: #43e97b; }
.muted { color: var(--muted); font-size: 10px; }
.empty-state { text-align: center; padding: 24px; color: var(--muted); }
.link-accent { color: var(--accent); text-decoration: none; font-weight: 600; }
.icon-sm { width: 12px; height: 12px; }

/* ── Modal ── */
.modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.75); z-index: 100;
    align-items: center; justify-content: center; padding: 16px;
}
.modal-card {
    width: 100%; max-width: 400px;
    max-height: 90vh; overflow-y: auto;
}
.modal-header {
    display: flex; align-items: center;
    justify-content: space-between; margin-bottom: 20px;
}
.modal-title { font-size: 16px; font-weight: 700; margin: 0; }
.modal-close {
    background: none; border: none; color: var(--muted);
    cursor: pointer; padding: 0; width: 24px; height: 24px;
    display: flex; align-items: center; justify-content: center;
}
.modal-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 12px; margin-bottom: 16px;
}
.form-group { margin-bottom: 16px; }
.form-label {
    display: block; font-size: 12px; font-weight: 600;
    margin-bottom: 6px; text-transform: uppercase; color: var(--muted);
}

/* ── Visibility helpers ── */
.desktop-only { display: block; }
.mobile-only  { display: none; }

/* ── Mobile product cards ── */
.product-card-mobile {
    padding: 8px 12px;
    border-bottom: 1px solid var(--border);
}
.pcm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}
.pcm-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
}
.pcm-stat {
    background: var(--surface2);
    border-radius: 4px;
    padding: 5px 7px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.pcm-label {
    font-size: 9px;
    text-transform: uppercase;
    color: var(--muted);
    font-weight: 600;
    letter-spacing: 0.03em;
}
.pcm-value {
    font-size: 11px;
    font-weight: 700;
}

/* ── Responsive breakpoints ── */
@media (max-width: 768px) {
    .prod-grid-top     { grid-template-columns: 1fr; }
    .bars-scroll       { max-height: 220px; }
    .modal-row         { grid-template-columns: 1fr; }
    .modal-backdrop    { padding: 0; align-items: flex-end; }
    .modal-card        { max-height: 92vh; border-radius: 16px 16px 0 0; max-width: 100%; }
    .section-header    { padding: 12px 14px; flex-direction: column; align-items: flex-start; }
    .section-actions   { width: 100%; }
    .select-compact    { flex: 1; min-width: 0; }
    .btn-sm            { flex: 1; justify-content: center; display: flex; }
    .desktop-only      { display: none !important; }
    .mobile-only       { display: block; }
}

@media (max-width: 480px) {
    .pcm-stats         { grid-template-columns: repeat(3, 1fr); }
    .page-header h1    { font-size: 18px; }
    .bars-scroll       { max-height: 180px; }
}
</style>

@endsection