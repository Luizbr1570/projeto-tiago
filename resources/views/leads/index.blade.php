@extends('layouts.app')
@section('title', 'Leads')

@section('content')
<div class="page-header">
    <h1>Leads — {{ auth()->user()->company->name }}</h1>
    <p>Todos os contatos recebidos via WhatsApp</p>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:13px;color:var(--muted);">{{ $leads->total() }} leads encontrados</span>
    <button onclick="document.getElementById('modal-lead').style.display='flex'" class="btn btn-primary">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Novo Lead
    </button>
</div>

{{-- Tabela — desktop --}}
<div class="leads-table-desktop card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="min-width:540px;">
            <thead>
                <tr>
                    <th>Telefone</th>
                    <th>Cidade</th>
                    <th>Status</th>
                    <th>Origem</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr data-removable>
                    <td>
                        <a href="{{ route('leads.show', $lead->id) }}" style="color:var(--text);font-weight:500;text-decoration:none;">
                            {{ $lead->phone }}
                        </a>
                    </td>
                    <td style="color:var(--muted);">{{ $lead->city ?? '—' }}</td>
                    <td><span class="badge badge-{{ $lead->status }}">{{ str_replace('_',' ',$lead->status) }}</span></td>
                    <td style="color:var(--muted);">{{ $lead->source ?? '—' }}</td>
                    <td style="color:var(--muted);">{{ $lead->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-ghost" style="padding:5px 10px;font-size:12px;">
                                <i data-lucide="eye" style="width:12px;height:12px;"></i>
                            </a>
                            <button type="button"
                                class="btn btn-danger"
                                style="padding:5px 10px;font-size:12px;"
                                data-delete-url="{{ route('leads.destroy', $lead->id) }}"
                                onclick="confirmDelete(this,'Lead','{{ route('leads.destroy', $lead->id) }}','{{ route('leads.restore', $lead->id) }}')">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">Nenhum lead encontrado</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if($leads->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface) 0%,var(--surface2) 100%);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            📊 Mostrando <strong style="color:var(--accent);">{{ $leads->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $leads->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $leads->total() }}</strong> leads
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if($leads->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $leads->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">← Anterior</a>
            @endif
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                @foreach($leads->getUrlRange(1, $leads->lastPage()) as $page => $url)
                    @if($page == $leads->currentPage())
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;min-width:36px;text-align:center;display:inline-block;"
                           onmouseover="this.style.background='var(--accent)';this.style.color='#fff'"
                           onmouseout="this.style.background='var(--surface2)';this.style.color='var(--text)'">{{ $page }}</a>
                    @endif
                @endforeach
            </div>
            @if($leads->hasMorePages())
                <a href="{{ $leads->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">Próxima →</a>
            @else
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Cards — mobile --}}
<div class="leads-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($leads as $lead)
    <div class="card" data-removable style="padding:14px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <a href="{{ route('leads.show', $lead->id) }}" style="font-size:14px;font-weight:600;color:var(--text);text-decoration:none;display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#a855f7;flex-shrink:0;">
                    {{ strtoupper(substr($lead->phone, -2)) }}
                </div>
                {{ $lead->phone }}
            </a>
            <span class="badge badge-{{ $lead->status }}">{{ str_replace('_',' ',$lead->status) }}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:12px;">
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">CIDADE</div>
                <div style="font-size:12px;">{{ $lead->city ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">ORIGEM</div>
                <div style="font-size:12px;">{{ $lead->source ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">CRIADO EM</div>
                <div style="font-size:12px;">{{ $lead->created_at->format('d/m/Y') }}</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-ghost" style="flex:1;justify-content:center;font-size:12px;padding:7px;">
                <i data-lucide="eye" style="width:13px;height:13px;"></i> Ver detalhes
            </a>
            <button type="button"
                class="btn btn-danger"
                style="padding:7px 12px;font-size:12px;"
                data-delete-url="{{ route('leads.destroy', $lead->id) }}"
                onclick="confirmDelete(this,'Lead','{{ route('leads.destroy', $lead->id) }}','{{ route('leads.restore', $lead->id) }}')">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
            </button>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhum lead encontrado</div>
    @endforelse

    {{-- Paginação mobile --}}
    @if($leads->hasPages())
    <div style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;color:var(--muted);">
            {{ $leads->firstItem() }}–{{ $leads->lastItem() }} de {{ $leads->total() }}
        </div>
        <div style="display:flex;gap:8px;">
            @if($leads->onFirstPage())
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $leads->previousPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
            @endif
            @if($leads->hasMorePages())
                <a href="{{ $leads->nextPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
            @else
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Modal novo lead --}}
<div id="modal-lead" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;padding:16px;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:400px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Novo Lead</h3>
            <button onclick="document.getElementById('modal-lead').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('leads.store') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label>Telefone *</label>
                <input type="text" name="phone" class="input" placeholder="(11) 99999-9999" required>
            </div>
            <div class="modal-lead-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div><label>Cidade</label><input type="text" name="city" class="input" placeholder="São Paulo"></div>
                <div><label>Origem</label><input type="text" name="source" class="input" placeholder="WhatsApp"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Criar Lead</button>
        </form>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .leads-table-desktop { display: none !important; }
    .leads-cards-mobile  { display: flex !important; }
}
@media (max-width: 480px) {
    .modal-lead-row { grid-template-columns: 1fr !important; }
}
</style>

@endsection