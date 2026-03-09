@extends('layouts.app')
@section('title', 'Leads')

@section('content')
<div class="page-header">
    <h1>Leads — {{ auth()->user()->company->name }}</h1>
    <p>Todos os contatos recebidos via WhatsApp</p>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <span style="font-size:13px;color:var(--muted);">{{ $leads->total() }} leads encontrados</span>
    <button onclick="document.getElementById('modal-lead').style.display='flex'" class="btn btn-primary">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> Novo Lead
    </button>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table>
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
            <tr>
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
                        <form method="POST" action="{{ route('leads.destroy', $lead->id) }}" onsubmit="return confirm('Remover?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:12px;">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">Nenhum lead encontrado</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($leads->hasPages())
<div style="margin-top:16px;">{{ $leads->links() }}</div>
@endif

<div id="modal-lead" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100;align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
    <div class="card" style="width:100%;max-width:400px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:700;">Novo Lead</h3>
            <button onclick="document.getElementById('modal-lead').style.display='none'" style="background:none;border:none;color:var(--muted);cursor:pointer;"><i data-lucide="x" style="width:16px;height:16px;"></i></button>
        </div>
        <form method="POST" action="{{ route('leads.store') }}">
            @csrf
            <div style="margin-bottom:14px;"><label>Telefone *</label><input type="text" name="phone" class="input" placeholder="(11) 99999-9999" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div><label>Cidade</label><input type="text" name="city" class="input" placeholder="São Paulo"></div>
                <div><label>Origem</label><input type="text" name="source" class="input" placeholder="WhatsApp"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Criar Lead</button>
        </form>
    </div>
</div>
@endsection
