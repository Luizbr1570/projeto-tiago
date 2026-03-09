@extends('layouts.app')
@section('title', 'Relatórios')

@section('content')
<div class="page-header">
    <h1>Relatórios — {{ auth()->user()->company->name }}</h1>
    <p>Métricas diárias de performance</p>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:14px;font-weight:600;">Métricas diárias</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Leads</th>
                <th>Conversas</th>
                <th>Recuperados</th>
                <th>Receita estimada</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics as $m)
            <tr>
                <td style="font-weight:500;">{{ \Carbon\Carbon::parse($m->date)->format('d/m/Y') }}</td>
                <td>{{ number_format($m->leads) }}</td>
                <td>{{ number_format($m->conversations) }}</td>
                <td><span style="color:#43e97b;font-weight:600;">{{ number_format($m->recovered_leads) }}</span></td>
                <td style="font-weight:600;color:#a855f7;">R$ {{ number_format($m->estimated_revenue, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:36px;color:var(--muted);">Nenhuma métrica ainda</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($metrics->hasPages())
<div style="margin-top:16px;">{{ $metrics->links() }}</div>
@endif
@endsection
