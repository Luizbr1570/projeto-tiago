@extends('layouts.app')
@section('title', 'Sessões de Chat')

@section('content')
<div class="page-header">
    <h1>Sessões de Chat — {{ auth()->user()->company->name }}</h1>
    <p>Sessões de atendimento abertas e encerradas</p>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Lead</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Transferido</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $session)
            <tr>
                <td style="font-weight:500;">{{ $session->lead->phone ?? '—' }}</td>
                <td style="color:var(--muted);">{{ $session->started_at ? \Carbon\Carbon::parse($session->started_at)->format('d/m/Y H:i') : '—' }}</td>
                <td style="color:var(--muted);">{{ $session->ended_at ? \Carbon\Carbon::parse($session->ended_at)->format('d/m/Y H:i') : '<span style="color:#43e97b;font-weight:600;">Ativa</span>' }}</td>
                <td>
                    @if($session->transferred_to_human)
                        <span style="color:#ec4899;font-size:12px;font-weight:600;">✓ Humano</span>
                    @else
                        <span style="color:var(--muted);font-size:12px;">IA</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        @if(!$session->transferred_to_human)
                        <form method="POST" action="{{ route('chat-sessions.transfer', $session->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:11px;">
                                <i data-lucide="arrow-right-left" style="width:11px;height:11px;"></i> Transferir
                            </button>
                        </form>
                        @endif
                        @if(!$session->ended_at)
                        <form method="POST" action="{{ route('chat-sessions.close', $session->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:11px;">
                                <i data-lucide="x" style="width:11px;height:11px;"></i> Encerrar
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma sessão encontrada</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($sessions->hasPages())
<div style="margin-top:16px;">{{ $sessions->links() }}</div>
@endif
@endsection
