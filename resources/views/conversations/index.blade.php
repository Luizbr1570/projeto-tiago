@extends('layouts.app')
@section('title', 'Atendimento')

@section('content')

@if(session('success'))
<div style="margin-bottom:16px;padding:14px 16px;background:rgba(67,233,123,0.1);border:1px solid rgba(67,233,123,0.3);border-radius:8px;color:#43e97b;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;animation:slideDown 0.3s ease;">
    <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom:16px;padding:14px 16px;background:rgba(255,65,100,0.1);border:1px solid rgba(255,65,100,0.3);border-radius:8px;color:#ff6584;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;animation:slideDown 0.3s ease;">
    <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
    {{ session('error') }}
</div>
@endif

<div class="page-header">
    <h1>Atendimento — {{ auth()->user()->company->name }}</h1>
    <p>Histórico de todas as conversas</p>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('conversations.index') }}">
    <div class="conv-filters" style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;margin-bottom:20px;">
        <input type="text" name="search" placeholder="🔍 Buscar por telefone ou mensagem..."
            value="{{ request('search') }}"
            style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--text);font-size:13px;outline:none;font-family:inherit;">

        <select name="sender" style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--text);font-size:13px;font-family:inherit;">
            <option value="">📋 Todos os remetentes</option>
            <option value="lead"  {{ request('sender')=='lead'  ?'selected':'' }}>👤 Lead</option>
            <option value="bot"   {{ request('sender')=='bot'   ?'selected':'' }}>🤖 Bot (IA)</option>
            <option value="human" {{ request('sender')=='human' ?'selected':'' }}>👨‍💼 Humano</option>
        </select>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary" style="padding:10px 18px;font-size:13px;white-space:nowrap;">
                <i data-lucide="search" style="width:14px;height:14px;"></i> Filtrar
            </button>
            @if(request()->hasAny(['search', 'sender']))
            <a href="{{ route('conversations.index') }}" class="btn btn-ghost" style="padding:10px 18px;font-size:13px;white-space:nowrap;">
                <i data-lucide="x" style="width:14px;height:14px;"></i> Limpar
            </a>
            @endif
        </div>
    </div>
</form>

{{-- Resumo --}}
<div class="conv-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;">
        <div style="font-size:11px;color:var(--muted);font-weight:600;margin-bottom:4px;text-transform:uppercase;">Total de conversas</div>
        <div style="font-size:24px;font-weight:700;">{{ $conversations->total() }}</div>
    </div>
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;">
        <div style="font-size:11px;color:var(--muted);font-weight:600;margin-bottom:4px;text-transform:uppercase;">Nesta página</div>
        <div style="font-size:24px;font-weight:700;">{{ $conversations->count() }}</div>
    </div>
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;">
        <div style="font-size:11px;color:var(--muted);font-weight:600;margin-bottom:4px;text-transform:uppercase;">Tempo médio resposta</div>
        <div style="font-size:24px;font-weight:700;">
            @php $avgTime = $conversations->avg('response_time'); echo $avgTime ? round($avgTime/1000,2).'s' : '—'; @endphp
        </div>
    </div>
</div>

@if($conversations->count() > 0)

{{-- Tabela — desktop --}}
<div class="conv-table-desktop card" style="padding:0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead style="background:linear-gradient(135deg,var(--surface2),var(--surface3));border-bottom:2px solid var(--border);">
                <tr>
                    <th style="padding:14px;text-align:left;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Lead</th>
                    <th style="padding:14px;text-align:left;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Remetente</th>
                    <th style="padding:14px;text-align:left;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Mensagem</th>
                    <th style="padding:14px;text-align:center;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Tempo</th>
                    <th style="padding:14px;text-align:left;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Data</th>
                    <th style="padding:14px;text-align:center;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:0.5px;font-size:11px;">Ação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conversations as $conv)
                <tr data-removable style="border-bottom:1px solid var(--border);transition:all 0.2s ease;background:var(--surface);"
                    onmouseover="this.style.background='var(--surface2)'"
                    onmouseout="this.style.background='var(--surface)'">
                    <td style="padding:14px;font-weight:600;">
                        {{-- FIX: null-check no lead — pode ser null se o lead foi deletado --}}
                        @if($conv->lead)
                            <a href="{{ route('leads.show', $conv->lead->id) }}"
                               style="color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:8px;"
                               onmouseover="this.style.transform='translateX(4px)'"
                               onmouseout="this.style.transform='translateX(0)'">
                                <i data-lucide="phone" style="width:14px;height:14px;flex-shrink:0;"></i>
                                {{ $conv->lead->phone }}
                            </a>
                        @else
                            <span style="color:var(--muted);display:flex;align-items:center;gap:8px;">
                                <i data-lucide="phone-off" style="width:14px;height:14px;flex-shrink:0;"></i>
                                Lead removido
                            </span>
                        @endif
                    </td>
                    <td style="padding:14px;">
                        <span class="badge {{ \App\Helpers\ConversationHelper::senderBadge($conv->sender) }}"
                              style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:4px;display:inline-block;">
                            {{ \App\Helpers\ConversationHelper::senderLabel($conv->sender) }}
                        </span>
                    </td>
                    <td style="padding:14px;">
                        <div style="max-width:350px;position:relative;display:inline-block;width:100%;" class="message-cell" title="{{ $conv->message }}">
                            <span style="color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">
                                {{ Str::limit($conv->message, 80, '...') }}
                            </span>
                            <div class="message-tooltip" style="display:none;position:absolute;bottom:120%;left:0;background:var(--surface3);border:1px solid var(--border);border-radius:6px;padding:10px;font-size:12px;white-space:normal;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:400px;">
                                {{ $conv->message }}
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px;text-align:center;">
                        @if($conv->response_time)
                            <span style="background:rgba(168,85,247,0.1);color:var(--accent);padding:5px 10px;border-radius:4px;font-weight:600;font-size:12px;">
                                ⚡ {{ round($conv->response_time/1000,2) }}s
                            </span>
                        @else
                            <span style="color:var(--muted);font-size:12px;">—</span>
                        @endif
                    </td>
                    <td style="padding:14px;color:var(--muted);font-size:12px;white-space:nowrap;">
                        {{ $conv->created_at->format('d/m/Y') }}<br>
                        <span style="font-size:11px;color:var(--muted2);">{{ $conv->created_at->format('H:i') }}</span>
                    </td>
                    <td style="padding:14px;text-align:center;">
                        <button type="button"
                            class="btn btn-danger"
                            style="padding:8px 12px;font-size:11px;"
                            data-delete-url="{{ route('conversations.destroy', $conv->id) }}"
                            onmouseover="this.style.transform='scale(1.05)'"
                            onmouseout="this.style.transform='scale(1)'"
                            onclick="confirmDelete(this,'Conversa','{{ route('conversations.destroy', $conv->id) }}','{{ route('conversations.restore', $conv->id) }}')">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginação desktop --}}
    @if($conversations->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface),var(--surface2));display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            📊 Mostrando <strong style="color:var(--accent);">{{ $conversations->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $conversations->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $conversations->total() }}</strong> conversas
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if($conversations->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">← Anterior</span>
            @else
                <a href="{{ $conversations->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">← Anterior</a>
            @endif
            @php
                $current = $conversations->currentPage();
                $last    = $conversations->lastPage();
                $from    = max(1, $current - 2);
                $to      = min($last, $current + 2);
                if ($to - $from < 4 && $last > 4) {
                    if ($from === 1) { $to = min($last, 5); }
                    elseif ($to === $last) { $from = max(1, $last - 4); }
                }
            @endphp
            <div style="display:flex;gap:4px;align-items:center;">
                @if($from > 1)
                    <a href="{{ $conversations->url(1) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">1</a>
                    @if($from > 2)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                @endif
                @for($page = $from; $page <= $to; $page++)
                    @if($page == $current)
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">{{ $page }}</span>
                    @else
                        <a href="{{ $conversations->url($page) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $page }}</a>
                    @endif
                @endfor
                @if($to < $last)
                    @if($to < $last - 1)<span style="padding:0 4px;color:var(--muted);font-size:12px;">…</span>@endif
                    <a href="{{ $conversations->url($last) }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;min-width:36px;text-align:center;">{{ $last }}</a>
                @endif
            </div>
            @if($conversations->hasMorePages())
                <a href="{{ $conversations->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;"
                   onmouseover="this.style.transform='translateX(2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">Próxima →</a>
            @else
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;cursor:not-allowed;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Cards — mobile --}}
<div class="conv-cards-mobile" style="display:none;flex-direction:column;gap:10px;">
    @foreach($conversations as $conv)
    <div class="card" data-removable style="padding:14px 16px;">
        {{-- Header do card --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            {{-- FIX: null-check no lead --}}
            @if($conv->lead)
                <a href="{{ route('leads.show', $conv->lead->id) }}" style="display:flex;align-items:center;gap:8px;color:var(--accent);text-decoration:none;font-size:13px;font-weight:600;">
                    <i data-lucide="phone" style="width:13px;height:13px;flex-shrink:0;"></i>
                    {{ $conv->lead->phone }}
                </a>
            @else
                <span style="display:flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;">
                    <i data-lucide="phone-off" style="width:13px;height:13px;flex-shrink:0;"></i>
                    Lead removido
                </span>
            @endif
            <span class="badge {{ \App\Helpers\ConversationHelper::senderBadge($conv->sender) }}" style="font-size:11px;font-weight:600;padding:4px 10px;border-radius:4px;">
                {{ \App\Helpers\ConversationHelper::senderLabel($conv->sender) }}
            </span>
        </div>

        {{-- Mensagem --}}
        <div style="background:var(--surface2);border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:12px;color:var(--muted);line-height:1.5;">
            {{ Str::limit($conv->message, 120, '...') }}
        </div>

        {{-- Rodapé --}}
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:11px;color:var(--muted);">
                    {{ $conv->created_at->format('d/m/Y H:i') }}
                </span>
                @if($conv->response_time)
                <span style="background:rgba(168,85,247,0.1);color:var(--accent);padding:3px 8px;border-radius:4px;font-size:11px;font-weight:600;">
                    ⚡ {{ round($conv->response_time/1000,2) }}s
                </span>
                @endif
            </div>
            <button type="button"
                class="btn btn-danger"
                style="padding:6px 10px;font-size:11px;"
                data-delete-url="{{ route('conversations.destroy', $conv->id) }}"
                onclick="confirmDelete(this,'Conversa','{{ route('conversations.destroy', $conv->id) }}','{{ route('conversations.restore', $conv->id) }}')">
                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
            </button>
        </div>
    </div>
    @endforeach

    {{-- Paginação mobile --}}
    @if($conversations->hasPages())
    <div style="padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div style="font-size:12px;color:var(--muted);">
            {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }} de {{ $conversations->total() }}
        </div>
        <div style="display:flex;gap:8px;">
            @if($conversations->onFirstPage())
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">← Anterior</span>
            @else
                <a href="{{ $conversations->previousPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">← Anterior</a>
            @endif
            @if($conversations->hasMorePages())
                <a href="{{ $conversations->nextPageUrl() }}" style="padding:8px 14px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">Próxima →</a>
            @else
                <span style="padding:8px 14px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;opacity:0.5;">Próxima →</span>
            @endif
        </div>
    </div>
    @endif
</div>

@else
{{-- Estado vazio --}}
<div class="card" style="padding:64px 32px;text-align:center;">
    <svg style="width:64px;height:64px;margin:0 auto 24px;opacity:0.2;animation:float 3s ease-in-out infinite;"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg>
    <h3 style="margin:0 0 12px;font-size:18px;color:var(--muted2);font-weight:700;">Nenhuma conversa encontrada</h3>
    <p style="margin:0 0 24px;font-size:14px;color:var(--muted);max-width:400px;margin-left:auto;margin-right:auto;">
        @if(request()->hasAny(['search','sender']))
            Tente ajustar seus filtros ou use outros termos.
        @else
            Nenhuma conversa registrada ainda.
        @endif
    </p>
    @if(request()->hasAny(['search','sender']))
    <a href="{{ route('conversations.index') }}" class="btn btn-primary">
        <i data-lucide="undo-2" style="width:14px;height:14px;"></i> Voltar aos filtros
    </a>
    @endif
</div>
@endif

<style>
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
@keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
.message-cell:hover .message-tooltip { display:block !important; }

@media (max-width: 768px) {
    .conv-filters { grid-template-columns: 1fr !important; }
    .conv-stats   { grid-template-columns: 1fr 1fr !important; }
    .conv-stats > div:last-child { grid-column: span 2; }
    .conv-table-desktop { display: none !important; }
    .conv-cards-mobile  { display: flex !important; }
}
@media (max-width: 480px) {
    .conv-stats { grid-template-columns: 1fr !important; }
    .conv-stats > div:last-child { grid-column: span 1; }
}
</style>

@endsection