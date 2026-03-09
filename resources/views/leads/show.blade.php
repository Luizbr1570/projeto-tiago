@extends('layouts.app')
@section('title', 'Lead · ' . $lead->phone)

@section('content')
<div class="page-header">
    <h1>Lead · {{ $lead->phone }}</h1>
    <p>Detalhes e histórico de conversas</p>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;">
    <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-size:14px;font-weight:600;">Informações</span>
                <span class="badge badge-{{ $lead->status }}">{{ str_replace('_',' ',$lead->status) }}</span>
            </div>
            @foreach([['TELEFONE',$lead->phone],['CIDADE',$lead->city??'—'],['ORIGEM',$lead->source??'—'],['CRIADO EM',$lead->created_at->format('d/m/Y H:i')]] as [$l,$v])
            <div style="margin-bottom:12px;">
                <div style="font-size:10px;color:var(--muted);font-weight:600;margin-bottom:3px;">{{ $l }}</div>
                <div style="font-size:13px;">{{ $v }}</div>
            </div>
            @endforeach
        </div>
        <div class="card">
            <div style="font-size:13px;font-weight:600;margin-bottom:14px;">Atualizar status</div>
            <form method="POST" action="{{ route('leads.update', $lead->id) }}">
                @csrf @method('PATCH')
                <select name="status" class="input" style="margin-bottom:10px;">
                    @foreach(['novo','em_conversa','pediu_preco','encaminhado','perdido','recuperacao'] as $s)
                    <option value="{{ $s }}" {{ $lead->status===$s?'selected':'' }}>{{ str_replace('_',' ',ucfirst($s)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Salvar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px;">Histórico de conversas</div>
        <div style="display:flex;flex-direction:column;gap:10px;max-height:520px;overflow-y:auto;padding-right:4px;">
            @forelse($lead->conversations as $conv)
            <div style="display:flex;gap:10px;{{ $conv->sender==='human'?'flex-direction:row-reverse;':'' }}">
                <div style="width:28px;height:28px;border-radius:50%;background:{!! \App\Helpers\ConversationHelper::senderColor($conv->sender) !!};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="{{ \App\Helpers\ConversationHelper::senderIcon($conv->sender) }}" style="width:12px;height:12px;color:{{ \App\Helpers\ConversationHelper::senderIconColor($conv->sender) }};"></i>
                </div>
                <div style="max-width:72%;">
                    <div style="font-size:10px;color:var(--muted);margin-bottom:3px;">{{ ucfirst($conv->sender) }} · {{ $conv->created_at->format('d/m H:i') }}</div>
                    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:9px 13px;font-size:13px;line-height:1.5;">{{ $conv->message }}</div>
                </div>
            </div>
            @empty
            <p style="text-align:center;color:var(--muted);font-size:13px;padding:24px 0;">Nenhuma conversa ainda</p>
            @endforelse
        </div>
    </div>
</div>
@endsection