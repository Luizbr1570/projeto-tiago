@extends('layouts.app')
@section('title', 'Login Meta')

@section('content')
<style>
    .meta-grid { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:16px; }
    .meta-col-4 { grid-column:span 4; }
    .meta-col-8 { grid-column:span 8; }
    .meta-col-12 { grid-column:span 12; }
    .meta-stack { display:grid; gap:14px; }
    .meta-card-title { font-size:15px; font-weight:700; margin-bottom:4px; }
    .meta-card-subtitle { font-size:12px; color:var(--muted); margin-bottom:18px; }
    .meta-feedback { min-height:20px; font-size:12px; }
    .meta-feedback.error { color:#ff6584; }
    .meta-feedback.success { color:#43e97b; }
    .meta-status {
        display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
        background:rgba(67,233,123,0.12); color:#43e97b; font-size:12px; font-weight:700;
    }
    .meta-status[data-tone="warning"] { background:rgba(255,193,7,0.12); color:#ffc107; }
    .meta-status[data-tone="danger"] { background:rgba(255,101,132,0.12); color:#ff6584; }
    .meta-summary {
        display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px;
    }
    .meta-summary-item {
        background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:14px;
    }
    .meta-inline-label {
        display:block; font-size:11px; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.04em;
    }
    .meta-inline-value { font-size:13px; font-weight:600; word-break:break-word; }
    @media (max-width: 1100px) {
        .meta-col-4, .meta-col-8, .meta-col-12 { grid-column:span 12; }
        .meta-summary { grid-template-columns:1fr; }
    }
</style>

<div class="page-header">
    <h1>Login Meta</h1>
    <p>Validando a conexao da conta para finalizar o login do WhatsApp Business.</p>
</div>

@if($migrationRequired)
    <div class="alert alert-error">
        As tabelas da integracao Meta ainda nao existem neste banco. Execute <code>php artisan migrate</code> antes de usar esta area.
    </div>
@endif

@php
    $status = $latestSession?->connection_status ?: ($config->integration_status ?: 'pending');
    $tone = str_contains($status, 'error') ? 'danger' : (($status === 'pending' || $status === 'not_configured') ? 'warning' : 'success');
@endphp

<div class="meta-grid" id="metaEmbeddedSignupCallbackApp"
     data-exchange-endpoint="{{ route('api.meta.embedded-signup.exchange-code') }}"
     data-latest-endpoint="{{ route('api.meta.embedded-signup.latest') }}"
     data-migration-required="{{ $migrationRequired ? '1' : '0' }}">

    <section class="card meta-col-4">
        <div class="meta-card-title">Status do login</div>
        <div class="meta-card-subtitle">A pagina atualiza automaticamente quando a Meta concluir a validacao.</div>

        <div class="meta-stack">
            <div class="meta-status" data-tone="{{ $tone }}">
                <i data-lucide="badge-check" style="width:14px;height:14px;"></i>
                <span id="callbackLatestStatus">{{ $status }}</span>
            </div>

            <div id="callbackFeedback" class="meta-feedback"></div>

            <div class="meta-stack">
                <a href="{{ route('admin.meta.embedded-signup.index') }}" class="btn btn-primary">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Voltar para Login Meta
                </a>
            </div>
        </div>
    </section>

    <section class="card meta-col-8">
        <div class="meta-card-title">Informacoes da conta</div>
        <div class="meta-card-subtitle">Exibimos somente os dados principais retornados pelo login.</div>

        <div class="meta-summary">
            <div class="meta-summary-item">
                <span class="meta-inline-label">Nome</span>
                <div class="meta-inline-value" id="callbackDisplayName">{{ $latestSession?->display_name ?? 'Aguardando retorno' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Evento</span>
                <div class="meta-inline-value" id="callbackLatestEvent">{{ $latestSession?->event_type ?? 'Aguardando retorno' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">WhatsApp Business ID</span>
                <div class="meta-inline-value" id="callbackWabaId">{{ $latestSession?->waba_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Numero ID</span>
                <div class="meta-inline-value" id="callbackPhoneNumberId">{{ $latestSession?->phone_number_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Business ID</span>
                <div class="meta-inline-value" id="callbackBusinessId">{{ $latestSession?->business_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Atualizado em</span>
                <div class="meta-inline-value" id="callbackLatestAt">{{ $latestSession?->created_at?->format('d/m/Y H:i:s') ?? 'Aguardando retorno' }}</div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const root = document.getElementById('metaEmbeddedSignupCallbackApp');
        if (!root) {
            return;
        }

        const migrationRequired = root.dataset.migrationRequired === '1';
        const storageKey = 'meta_embedded_signup_session_data';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const callbackFeedback = document.getElementById('callbackFeedback');
        const latestStatus = document.getElementById('callbackLatestStatus');
        const latestEvent = document.getElementById('callbackLatestEvent');
        const latestAt = document.getElementById('callbackLatestAt');
        const displayName = document.getElementById('callbackDisplayName');
        const wabaId = document.getElementById('callbackWabaId');
        const phoneNumberId = document.getElementById('callbackPhoneNumberId');
        const businessId = document.getElementById('callbackBusinessId');
        const query = new URLSearchParams(window.location.search);
        const callbackCode = query.get('code');

        const setFeedback = (message, type = '') => {
            callbackFeedback.textContent = message || '';
            callbackFeedback.className = `meta-feedback ${type}`.trim();
        };

        const readSessionData = () => {
            try {
                return JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
            } catch {
                return {};
            }
        };

        const syncLatestCard = (latest, config) => {
            latestStatus.textContent = latest?.connection_status || config?.integration_status || 'N/D';
            latestEvent.textContent = latest?.event_type || 'Aguardando retorno';
            latestAt.textContent = latest?.created_at ? new Date(latest.created_at).toLocaleString('pt-BR') : 'Aguardando retorno';
            displayName.textContent = latest?.display_name || 'Aguardando retorno';
            wabaId.textContent = latest?.waba_id || 'N/D';
            phoneNumberId.textContent = latest?.phone_number_id || 'N/D';
            businessId.textContent = latest?.business_id || 'N/D';
        };

        const refreshLatest = async () => {
            const response = await fetch(root.dataset.latestEndpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json().catch(() => ({}));
            syncLatestCard(data.latest, data.config);
        };

        const exchangeCode = async () => {
            if (migrationRequired) {
                setFeedback('Execute php artisan migrate antes de usar esta area.', 'error');
                return;
            }

            if (!callbackCode) {
                await refreshLatest();
                setFeedback('Aguardando o retorno do login Meta.', '');
                return;
            }

            setFeedback('Finalizando o login Meta...', '');

            const response = await fetch(root.dataset.exchangeEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: callbackCode,
                    session_data: readSessionData()
                })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Falha ao concluir o login Meta.');
            }

            await refreshLatest();
            setFeedback('Login Meta concluido com sucesso.', 'success');
        };

        (async () => {
            try {
                await exchangeCode();
            } catch (error) {
                setFeedback(error.message || 'Falha ao validar o retorno da Meta.', 'error');
            }
        })();
    })();
</script>
@endpush
