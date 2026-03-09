@extends('layouts.app')
@section('title', 'Recuperação')

@section('content')
<div class="page-header">
    <h1>Recuperação — {{ auth()->user()->company->name }}</h1>
    <p>Leads perdidos e follow-ups automáticos</p>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
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

<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Lead</th>
                <th>Status</th>
                <th>Enviado em</th>
                <th>Recuperado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($followups as $followup)
            <tr>
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
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">Nenhum follow-up encontrado</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
