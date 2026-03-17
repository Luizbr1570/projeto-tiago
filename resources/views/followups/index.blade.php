@extends('layouts.app')
@section('title', 'Recuperação')

@section('content')
<div class="page-header">
    <h1>Recuperação — {{ auth()->user()->company->name }}</h1>
    <p>Leads perdidos e follow-ups automáticos</p>
</div>

{{-- Cards de resumo --}}
<div class="recovery-cards" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
    <div class="card">
        <div style="font-size:11px;color:var(--muted);font-weight:600;letter-spacing:0.5px;margin-bottom:8px;">PENDENTES</div>
        <div style="font-size:28px;font-weight:700;color:#ffc107;">{{ $followups->where('status','pending')->count() }}</div>
    </div>
    <div class="card">
        <div style="font-size:11px;color:var(--muted);font-weight:600;letter-spacing:0.5px;margin-bottom:8px;">ENVIADOS</div>
        <div style="font-size:28px;font-weight:700;color:#a855f7;">{{ $followups->where('status','sent')->count() }}</div>
    </div>
    <div class="card" style="border-color:rgba(67,233,123,0.2);">
        <div style="font-size:11px;color:#43e97b;font-weight:600;letter-spacing:0.5px;margin-bottom:8px;">RECUPERADOS</div>
        <div style="font-size:28px;font-weight:700;color:#43e97b;">{{ $followups->where('status','recovered')->count() }}</div>
    </div>
</div>

{{-- Tabela — desktop --}}
<div class="followups-table-desktop card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="min-width:500px;">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Status</th>
                    <th>Enviado em</th>
                    <th>Recuperado</th>
                    <th>Alterar status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($followups as $followup)
                <tr data-removable>
                    <td style="font-weight:500;">{{ $followup->lead->phone ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $followup->status==='pending'?'badge-pediu_preco':($followup->status==='sent'?'badge-novo':'badge-em_conversa') }}">
                            {{ ucfirst($followup->status) }}
                        </span>
                    </td>
                    <td style="color:var(--muted);">{{ $followup->sent_at ? $followup->sent_at->format('d/m/Y H:i') : '—' }}</td>
                    <td>
                        @if($followup->recovered)
                            <span style="color:#43e97b;font-size:12px;font-weight:600;">✓ Sim</span>
                        @else
                            <span style="color:var(--muted);font-size:12px;">Não</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('followups.update', $followup->id) }}">
                            @csrf @method('PATCH')
                            <select name="status" class="input" style="width:120px;padding:5px 8px;font-size:12px;" onchange="this.form.submit()">
                                @foreach(['pending','sent','recovered'] as $s)
                                <option value="{{ $s }}" {{ $followup->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <button type="button"
                            class="btn btn-danger"
                            style="padding:5px 10px;font-size:11px;"
                            data-delete-url="{{ route('followups.destroy', $followup->id) }}"
                            onclick="confirmDelete(this,'Follow-up','{{ route('followups.destroy', $followup->id) }}','{{ route('followups.restore', $followup->id) }}')">
                            <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">Nenhum follow-up encontrado</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Cards — mobile --}}
<div class="followups-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($followups as $followup)
    <div class="card" data-removable style="padding:14px 16px;">
        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="phone" style="width:13px;height:13px;color:#a855f7;"></i>
                </div>
                <span style="font-size:14px;font-weight:600;">{{ $followup->lead->phone ?? '—' }}</span>
            </div>
            <span class="badge {{ $followup->status==='pending'?'badge-pediu_preco':($followup->status==='sent'?'badge-novo':'badge-em_conversa') }}">
                {{ ucfirst($followup->status) }}
            </span>
        </div>

        {{-- Infos --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">ENVIADO EM</div>
                <div style="font-size:12px;">{{ $followup->sent_at ? $followup->sent_at->format('d/m/Y H:i') : '—' }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">RECUPERADO</div>
                <div style="font-size:12px;">
                    @if($followup->recovered)
                        <span style="color:#43e97b;font-weight:600;">✓ Sim</span>
                    @else
                        <span style="color:var(--muted);">Não</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Select + Botão remover --}}
        <div style="display:flex;gap:8px;align-items:center;">
            <form method="POST" action="{{ route('followups.update', $followup->id) }}" style="flex:1;">
                @csrf @method('PATCH')
                <select name="status" class="input" style="width:100%;padding:8px 12px;font-size:13px;" onchange="this.form.submit()">
                    @foreach(['pending','sent','recovered'] as $s)
                    <option value="{{ $s }}" {{ $followup->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button"
                class="btn btn-danger"
                style="padding:8px 12px;font-size:12px;flex-shrink:0;"
                data-delete-url="{{ route('followups.destroy', $followup->id) }}"
                onclick="confirmDelete(this,'Follow-up','{{ route('followups.destroy', $followup->id) }}','{{ route('followups.restore', $followup->id) }}')">
                <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
            </button>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhum follow-up encontrado</div>
    @endforelse
</div>

<style>
@media (max-width: 768px) {
    .recovery-cards          { grid-template-columns: repeat(3,1fr) !important; }
    .followups-table-desktop { display: none !important; }
    .followups-cards-mobile  { display: flex !important; }
}
@media (max-width: 480px) {
    .recovery-cards { grid-template-columns: 1fr !important; }
}
</style>

@endsection