@extends('layouts.app')
@section('title', 'Atendimento')

@section('content')
{{-- Mensagens de feedback --}}
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
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;margin-bottom:20px;">
    <form method="GET" action="{{ route('conversations.index') }}" style="display:contents;">
        <input type="text" name="search" placeholder="🔍 Buscar por telefone ou mensagem..." 
            value="{{ request('search') }}" 
            style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--text);font-size:13px;">
        
        <select name="sender" style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--text);font-size:13px;">
            <option value="">📋 Todos os remetentes</option>
            <option value="lead" {{ request('sender')=='lead'?'selected':'' }}>👤 Lead</option>
            <option value="bot" {{ request('sender')=='bot'?'selected':'' }}>🤖 Bot (IA)</option>
            <option value="human" {{ request('sender')=='human'?'selected':'' }}>👨‍💼 Humano</option>
        </select>
        
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary" style="padding:10px 18px;font-size:13px;font-weight:600;">
                <i data-lucide="search" style="width:14px;height:14px;margin-right:6px;vertical-align:middle;"></i> Filtrar
            </button>
            
            @if(request()->hasAny(['search', 'sender']))
            <a href="{{ route('conversations.index') }}" class="btn btn-ghost" style="padding:10px 18px;font-size:13px;">
                <i data-lucide="x" style="width:14px;height:14px;margin-right:6px;vertical-align:middle;"></i> Limpar
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Resumo --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
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
            @php
                $avgTime = $conversations->avg('response_time');
                echo $avgTime ? round($avgTime / 1000, 2) . 's' : '—';
            @endphp
        </div>
    </div>
</div>

{{-- Tabela --}}
<div class="card" style="padding:0;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
    @if($conversations->count() > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead style="background:linear-gradient(135deg,var(--surface2) 0%,var(--surface3) 100%);border-bottom:2px solid var(--border);">
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
                <tr style="border-bottom:1px solid var(--border);transition:all 0.2s ease;background:var(--surface);" 
                    onmouseover="this.style.background='var(--surface2)'" 
                    onmouseout="this.style.background='var(--surface)'">
                    
                    {{-- Lead --}}
                    <td style="padding:14px;font-weight:600;">
                        <a href="{{ route('leads.show', $conv->lead->id) }}" 
                           style="color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:8px;transition:all 0.2s;"
                           onmouseover="this.style.transform='translateX(4px)'"
                           onmouseout="this.style.transform='translateX(0)'">
                            <i data-lucide="phone" style="width:14px;height:14px;flex-shrink:0;"></i>
                            {{ $conv->lead->phone ?? '—' }}
                        </a>
                    </td>
                    
                    {{-- Remetente --}}
                    <td style="padding:14px;">
                        <span class="badge {{ \App\Helpers\ConversationHelper::senderBadge($conv->sender) }}" 
                              style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:4px;display:inline-block;">
                            {{ \App\Helpers\ConversationHelper::senderLabel($conv->sender) }}
                        </span>
                    </td>
                    
                    {{-- Mensagem com tooltip --}}
                    <td style="padding:14px;">
                        <div style="max-width:350px;position:relative;display:inline-block;width:100%;" 
                             class="message-cell"
                             title="{{ $conv->message }}">
                            <span style="color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">
                                {{ Str::limit($conv->message, 80, '...') }}
                            </span>
                            <div class="message-tooltip" style="display:none;position:absolute;bottom:120%;left:0;right:0;background:var(--surface3);border:1px solid var(--border);border-radius:6px;padding:10px;font-size:12px;white-space:normal;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:400px;">
                                {{ $conv->message }}
                            </div>
                        </div>
                    </td>
                    
                    {{-- Tempo de resposta --}}
                    <td style="padding:14px;text-align:center;">
                        @if($conv->response_time)
                            <span style="background:rgba(168,85,247,0.1);color:var(--accent);padding:5px 10px;border-radius:4px;font-weight:600;display:inline-block;font-size:12px;">
                                ⚡ {{ round($conv->response_time / 1000, 2) }}s
                            </span>
                        @else
                            <span style="color:var(--muted);font-size:12px;">—</span>
                        @endif
                    </td>
                    
                    {{-- Data --}}
                    <td style="padding:14px;color:var(--muted);font-size:12px;white-space:nowrap;">
                        {{ $conv->created_at->format('d/m/Y') }}<br>
                        <span style="font-size:11px;color:var(--muted2);">{{ $conv->created_at->format('H:i') }}</span>
                    </td>
                    
                    {{-- Ação --}}
                    <td style="padding:14px;text-align:center;">
                        <form method="POST" action="{{ route('conversations.destroy', $conv->id) }}" 
                              onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita.');" 
                              style="display:inline;"
                              class="delete-form">
                            @csrf @method('DELETE')
                            <button type="submit" 
                                    class="btn btn-danger" 
                                    style="padding:8px 12px;font-size:11px;transition:all 0.2s;cursor:pointer;"
                                    onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 2px 8px rgba(255,65,100,0.3)'"
                                    onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'"
                                    title="Remover esta conversa">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if($conversations->hasPages())
    <div style="padding:20px;border-top:2px solid var(--border);background:linear-gradient(135deg,var(--surface) 0%,var(--surface2) 100%);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="font-size:13px;color:var(--muted);font-weight:500;">
            📊 Mostrando <strong style="color:var(--accent);">{{ $conversations->firstItem() }}</strong> a <strong style="color:var(--accent);">{{ $conversations->lastItem() }}</strong> de <strong style="color:var(--accent);">{{ $conversations->total() }}</strong> conversas
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            {{-- Botão Previous --}}
            @if($conversations->onFirstPage())
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">
                    ← Anterior
                </span>
            @else
                <a href="{{ $conversations->previousPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(-2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">
                    ← Anterior
                </a>
            @endif
            
            {{-- Números de página --}}
            <div style="display:flex;gap:4px;">
                @foreach($conversations->getUrlRange(1, $conversations->lastPage()) as $page => $url)
                    @if($page == $conversations->currentPage())
                        <span style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;font-weight:700;min-width:36px;text-align:center;">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--text);text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;min-width:36px;text-align:center;display:inline-block;"
                           onmouseover="this.style.background='var(--accent)';this.style.color='#fff'"
                           onmouseout="this.style.background='var(--surface2)';this.style.color='var(--text)'">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            </div>
            
            {{-- Botão Next --}}
            @if($conversations->hasMorePages())
                <a href="{{ $conversations->nextPageUrl() }}" style="padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;transition:all 0.2s;display:inline-flex;align-items:center;gap:6px;"
                   onmouseover="this.style.transform='translateX(2px)';this.style.boxShadow='0 4px 12px rgba(168,85,247,0.3)'"
                   onmouseout="this.style.transform='translateX(0)';this.style.boxShadow='none'">
                    Próxima →
                </a>
            @else
                <span style="padding:8px 12px;border-radius:6px;background:var(--surface2);color:var(--muted);font-size:12px;font-weight:600;cursor:not-allowed;opacity:0.5;">
                    Próxima →
                </span>
            @endif
        </div>
    </div>
    @endif

    @else
    {{-- Estado vazio com animação --}}
    <div style="padding:64px 32px;text-align:center;background:linear-gradient(135deg,var(--surface) 0%,var(--surface2) 100%);">
        <div style="margin-bottom:24px;">
            <svg style="width:64px;height:64px;margin:0 auto;opacity:0.2;animation:float 3s ease-in-out infinite;" 
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <h3 style="margin:0 0 12px;font-size:18px;color:var(--muted2);font-weight:700;">Nenhuma conversa encontrada</h3>
        <p style="margin:0 0 24px;font-size:14px;color:var(--muted);max-width:400px;margin-left:auto;margin-right:auto;">
            @if(request()->hasAny(['search', 'sender']))
                Tente ajustar seus filtros de busca ou tente novos termos
            @else
                Nenhuma conversa foi registrada ainda nesta empresa
            @endif
        </p>
        @if(request()->hasAny(['search', 'sender']))
        <a href="{{ route('conversations.index') }}" class="btn btn-primary" style="display:inline-block;padding:10px 20px;margin-top:16px;">
            <i data-lucide="undo-2" style="width:14px;height:14px;margin-right:6px;vertical-align:middle;"></i>
            Voltar aos filtros
        </a>
        @endif
    </div>
    @endif
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-cell:hover .message-tooltip {
    display:block !important;
}

.btn {
    transition: all 0.2s ease;
}

.btn:active {
    transform: scale(0.98) !important;
}

table tr:hover {
    box-shadow: inset 0 0 10px rgba(168,85,247,0.1);
}

@media (max-width: 768px) {
    table { font-size: 11px; }
    th, td { padding: 10px !important; }
    .message-tooltip { max-width: 300px; }
}
</style>

@endsection