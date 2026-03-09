@extends('layouts.app')
@section('title', 'Insights IA')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;">
    <div>
        <h1>Insights da IA</h1>
        <p>Análise automática do seu atendimento</p>
    </div>
    <form method="POST" action="{{ route('insights.store') }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Gerar novo insight
        </button>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
    @forelse($insights as $insight)
    <div class="card" style="border-color:rgba(168,85,247,0.2);background:rgba(168,85,247,0.03);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div style="display:flex;gap:10px;align-items:flex-start;flex:1;">
                <div style="width:30px;height:30px;border-radius:7px;background:rgba(168,85,247,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                    <i data-lucide="sparkles" style="width:13px;height:13px;color:#a855f7;"></i>
                </div>
                <p style="font-size:13px;line-height:1.6;color:var(--muted2);">{{ $insight->insight }}</p>
            </div>
            <form method="POST" action="{{ route('insights.destroy', $insight->id) }}" onsubmit="return confirm('Remover?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="padding:5px 8px;">
                    <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                </button>
            </form>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
            {{ $insight->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
    @empty
    <div class="card" style="grid-column:span 2;text-align:center;padding:48px;">
        <i data-lucide="sparkles" style="width:32px;height:32px;color:var(--border);margin-bottom:12px;"></i>
        <p style="font-size:14px;color:var(--muted);margin-bottom:16px;">Nenhum insight gerado ainda</p>
        <form method="POST" action="{{ route('insights.store') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i data-lucide="zap" style="width:13px;height:13px;"></i> Gerar primeiro insight
            </button>
        </form>
    </div>
    @endforelse
</div>
@if($insights->hasPages())
<div style="margin-top:16px;">{{ $insights->links() }}</div>
@endif
@endsection
