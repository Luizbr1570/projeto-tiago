@extends('layouts.app')
@section('title', 'Produtos')

@section('content')
<div class="page-header">
    <h1>Produtos — {{ auth()->user()->company->name }}</h1>
    <p>O que os clientes mais procuram e quanto vale cada conversa</p>
</div>

{{-- ── Produtos mais buscados + Ticket médio ── --}}
<div class="prod-grid-top" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">

    {{-- Barra horizontal --}}
    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:18px;">Produtos mais buscados</div>
        @php $maxBusca = $products->max('interests_count') ?: 1; @endphp
        @forelse($products as $product)
        <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <span style="font-size:12px;color:var(--muted2);">{{ $product->name }}</span>
                <span style="font-size:12px;font-weight:600;">{{ $product->interests_count }}</span>
            </div>
            <div style="background:var(--surface2);border-radius:4px;height:8px;overflow:hidden;">
                @php $pct = round(($product->interests_count / $maxBusca) * 100); @endphp
                <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#a855f7,#ec4899);border-radius:4px;"></div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:32px;color:var(--muted);">
            Nenhum produto com interesse registrado
        </div>
        @endforelse
    </div>

    {{-- Ticket médio por produto --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:18px 20px;border-bottom:1px solid var(--border);">
            <div style="font-size:14px;font-weight:600;">Ticket médio por produto</div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;font-size:12px;">
                <thead style="background:var(--surface2);">
                    <tr>
                        <th style="padding:12px;text-align:left;">Produto</th>
                        <th style="padding:12px;text-align:right;">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:12px;font-weight:500;">{{ $product->name }}</td>
                        <td style="padding:12px;text-align:right;color:var(--accent);font-weight:600;">R$ {{ number_format($product->avg_price ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align:center;padding:24px;color:var(--muted);">Nenhum produto cadastrado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Tabela interações por produto ── --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="font-size:14px;font-weight:600;">Interações por produto</div>
        <button onclick="document.getElementById('modal-product').style.display='flex'" class="btn btn-primary" style="font-size:12px;padding:8px 16px;">
            <i data-lucide="plus" style="width:14px;height:14px;margin-right:6px;vertical-align:middle;"></i> Novo produto
        </button>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%;font-size:12px;">
            <thead style="background:var(--surface2);">
                <tr>
                    <th style="padding:12px;text-align:left;">Produto</th>
                    <th style="padding:12px;text-align:center;">Buscas</th>
                    <th style="padding:12px;text-align:right;">Ticket médio</th>
                    <th style="padding:12px;text-align:center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr data-removable style="border-bottom:1px solid var(--border);transition:background 0.2s;"
                    onmouseover="this.style.background='var(--surface2)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:6px;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#a855f7;flex-shrink:0;">
                                {{ strtoupper(substr($product->name, 0, 1)) }}
                            </div>
                            <span style="font-weight:500;">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td style="padding:12px;text-align:center;font-weight:600;">{{ $product->interests_count ?? 0 }}</td>
                    <td style="padding:12px;text-align:right;color:var(--accent);font-weight:600;">R$ {{ number_format($product->avg_price ?? 0, 2, ',', '.') }}</td>
                    <td style="padding:12px;text-align:center;">
                        <button type="button"
                            class="btn btn-danger"
                            style="padding:6px 10px;font-size:11px;"
                            title="Remover produto"
                            data-delete-url="{{ route('products.destroy', $product->id) }}"
                            onclick="confirmDelete(this,'Produto','{{ route('products.destroy', $product->id) }}','{{ route('products.restore', $product->id) }}')">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:32px;color:var(--muted);">
                        Nenhum produto cadastrado. <a href="#" onclick="document.getElementById('modal-product').style.display='flex';return false" style="color:var(--accent);text-decoration:none;font-weight:600;">Crie o primeiro!</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal novo produto --}}
<div id="modal-product" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:20px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:400px;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;margin:0;">Novo Produto</h3>
            <button onclick="document.getElementById('modal-product').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;padding:0;width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="x" style="width:18px;height:18px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;text-transform:uppercase;color:var(--muted);">Nome do produto *</label>
                <input type="text" name="name" class="input" placeholder="iPhone 15 Pro Max" required>
            </div>
            <div class="modal-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;text-transform:uppercase;color:var(--muted);">Categoria</label>
                    <input type="text" name="category" class="input" placeholder="Smartphone">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;text-transform:uppercase;color:var(--muted);">Preço médio (R$)</label>
                    <input type="number" name="avg_price" class="input" placeholder="6420" step="0.01">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:13px;font-weight:600;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i data-lucide="plus" style="width:14px;height:14px;"></i> Cadastrar Produto
            </button>
        </form>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .prod-grid-top { grid-template-columns: 1fr !important; }
    .modal-row     { grid-template-columns: 1fr !important; }
    #modal-product { padding: 12px !important; }
}
</style>

@endsection