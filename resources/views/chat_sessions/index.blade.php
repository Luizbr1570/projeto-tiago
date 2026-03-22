@extends('layouts.app')
@section('title', 'Sessões de Chat')

@section('content')
<div class="page-header">
    <h1>Sessões de Chat — {{ auth()->user()->company->name }}</h1>
    <p>Sessões de atendimento abertas e encerradas</p>
</div>

{{-- Tabela — desktop --}}
<div class="sessions-table-desktop card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table style="min-width:540px;">
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
                    <td style="color:var(--muted);">{!! $session->ended_at ? \Carbon\Carbon::parse($session->ended_at)->format('d/m/Y H:i') : '<span style="color:#43e97b;font-weight:600;">Ativa</span>' !!}</td>
                    <td>
                        @if($session->transferred_to_human)
                            <span style="color:#ec4899;font-size:12px;font-weight:600;">✓ Humano</span>
                        @else
                            <span style="color:var(--muted);font-size:12px;">IA</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
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

    {{-- Paginação desktop --}}
    @if($sessions->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface) 0%,var(--surface2) 100%);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            📊 Mostrando <strong style="color:var(--accent);">{{ $sessions->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $sessions->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $sessions->total() }}</strong> sessões
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if($sessions->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $sessions->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">← Anterior</a>
            @endif

            @php
                $current = $sessions->currentPage();
                $last    = $sessions->lastPage();
                $from    = max(1, $current - 2);
                $to      = min($last, $current + 2);
                if ($to - $from < 4 && $last > 4) {
                    if ($from === 1) { $to = min($last, 5); }
                    elseif ($to === $last) { $from = max(1, $last - 4); }
                }
            @endphp
            <div style="display:flex;gap:4px;align-items:center;">
                @if($from > 1)
                    <a href="{{ $sessions->url(1) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">1</a>
                    @if($from > 2)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                @endif
                @for($page = $from; $page <= $to; $page++)
                    @if($page == $current)
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">{{ $page }}</span>
                    @else
                        <a href="{{ $sessions->url($page) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $page }}</a>
                    @endif
                @endfor
                @if($to < $last)
                    @if($to < $last - 1)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                    <a href="{{ $sessions->url($last) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $last }}</a>
                @endif
            </div>

            @if($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
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
<div class="sessions-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @forelse($sessions as $session)
    <div class="card" style="padding:14px 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="phone" style="width:13px;height:13px;color:#a855f7;"></i>
                </div>
                <span style="font-size:14px;font-weight:600;">{{ $session->lead->phone ?? '—' }}</span>
            </div>
            {{-- Status da sessão --}}
            @if(!$session->ended_at)
                <span style="background:rgba(67,233,123,0.15);color:#43e97b;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;">● Ativa</span>
            @else
                <span style="background:var(--surface2);color:var(--muted);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;">Encerrada</span>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">INÍCIO</div>
                <div style="font-size:12px;">{{ $session->started_at ? \Carbon\Carbon::parse($session->started_at)->format('d/m/Y H:i') : '—' }}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">FIM</div>
                <div style="font-size:12px;">{!! $session->ended_at ? \Carbon\Carbon::parse($session->ended_at)->format('d/m/Y H:i') : '<span style="color:#43e97b;font-weight:600;">Ativa</span>' !!}</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:2px;">TRANSFERIDO</div>
                <div style="font-size:12px;">
                    @if($session->transferred_to_human)
                        <span style="color:#ec4899;font-weight:600;">✓ Humano</span>
                    @else
                        <span style="color:var(--muted);">IA</span>
                    @endif
                </div>
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            @if(!$session->transferred_to_human)
            <form method="POST" action="{{ route('chat-sessions.transfer', $session->id) }}" style="flex:1;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:12px;padding:7px;">
                    <i data-lucide="arrow-right-left" style="width:12px;height:12px;"></i> Transferir
                </button>
            </form>
            @endif
            @if(!$session->ended_at)
            <form method="POST" action="{{ route('chat-sessions.close', $session->id) }}" style="flex:1;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;font-size:12px;padding:7px;">
                    <i data-lucide="x" style="width:12px;height:12px;"></i> Encerrar
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma sessão encontrada</div>
    @endforelse

    {{-- Paginação mobile --}}
    @if($sessions->hasPages())
    <div style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;color:var(--muted);">
            {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} de {{ $sessions->total() }}
        </div>
        <div style="display:flex;gap:8px;">
            @if($sessions->onFirstPage())
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $sessions->previousPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
            @endif
            @if($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
            @else
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

<style>
@media (max-width: 768px) {
    .sessions-table-desktop { display: none !important; }
    .sessions-cards-mobile  { display: flex !important; }
}
</style>

@endsection