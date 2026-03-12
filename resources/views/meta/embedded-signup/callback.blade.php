@extends('layouts.app')
@section('title', 'Meta / Embedded Signup Callback')

@php
    $latestRawPayload = $latestSession?->raw_payload ?? [];
    $latestNormalizedPayload = $latestSession?->normalized_payload ?? [];
@endphp

@section('content')
<style>
    .meta-grid { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:16px; }
    .meta-col-4 { grid-column:span 4; }
    .meta-col-6 { grid-column:span 6; }
    .meta-col-12 { grid-column:span 12; }
    .meta-stack { display:grid; gap:14px; }
    .meta-card-title { font-size:15px; font-weight:700; margin-bottom:4px; }
    .meta-card-subtitle { font-size:12px; color:var(--muted); margin-bottom:18px; }
    .meta-inline-label { display:block; font-size:11px; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.04em; }
    .meta-inline-value { font-size:13px; font-weight:600; word-break:break-word; }
    .meta-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .meta-feedback { min-height:20px; font-size:12px; }
    .meta-feedback.error { color:#ff6584; }
    .meta-feedback.success { color:#43e97b; }
    .meta-status {
        display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
        background:rgba(67,233,123,0.12); color:#43e97b; font-size:12px; font-weight:700;
    }
    .meta-status[data-tone="warning"] { background:rgba(255,193,7,0.12); color:#ffc107; }
    .meta-status[data-tone="danger"] { background:rgba(255,101,132,0.12); color:#ff6584; }
    .json-block {
        background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:16px;
        font-size:12px; line-height:1.55; color:#b8c3ff; max-height:360px; overflow:auto; white-space:pre-wrap; word-break:break-word;
    }
    @media (max-width: 1100px) {
        .meta-col-4, .meta-col-6, .meta-col-12 { grid-column:span 12; }
    }
</style>

<div class="page-header">
    <h1>Callback do Embedded Signup</h1>
    <p>Esta tela recebe o retorno visual do fluxo, mostra o payload bruto e envia o <code>code</code> para troca de token no backend.</p>
</div>

@if($migrationRequired)
    <div class="alert alert-error">
        As tabelas da integração Meta ainda não existem neste banco. Execute <code>php artisan migrate</code> antes de usar esta área.
    </div>
@endif

<div class="meta-grid" id="metaEmbeddedSignupCallbackApp"
     data-exchange-endpoint="{{ route('api.meta.embedded-signup.exchange-code') }}"
     data-latest-endpoint="{{ route('api.meta.embedded-signup.latest') }}"
     data-migration-required="{{ $migrationRequired ? '1' : '0' }}">

    <section class="card meta-col-4">
        <div class="meta-card-title">Callback recebido</div>
        <div class="meta-card-subtitle">Parâmetros atuais da URL de retorno.</div>
        <div class="meta-stack">
            <div>
                <span class="meta-inline-label">Callback URL</span>
                <div class="meta-inline-value">{{ $config->redirect_uri }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Code</span>
                <div class="meta-inline-value" id="callbackCodeValue">{{ request('code', 'Não recebido') }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Estado</span>
                <div class="meta-inline-value">{{ request('state', 'N/D') }}</div>
            </div>
            <div id="callbackFeedback" class="meta-feedback"></div>
        </div>
    </section>

    <section class="card meta-col-4">
        <div class="meta-card-title">Status atual</div>
        <div class="meta-card-subtitle">Último retorno persistido para esta empresa.</div>
        @php
            $status = $latestSession?->connection_status ?: ($config->integration_status ?: 'pending');
            $tone = str_contains($status, 'error') ? 'danger' : (($status === 'pending' || $status === 'not_configured') ? 'warning' : 'success');
        @endphp
        <div class="meta-stack">
            <div class="meta-status" data-tone="{{ $tone }}">
                <i data-lucide="badge-check" style="width:14px;height:14px;"></i>
                <span id="callbackLatestStatus">{{ $status }}</span>
            </div>
            <div>
                <span class="meta-inline-label">Último evento</span>
                <div class="meta-inline-value" id="callbackLatestEvent">{{ $latestSession?->event_type ?? 'N/D' }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Último retorno salvo</span>
                <div class="meta-inline-value" id="callbackLatestAt">{{ $latestSession?->created_at?->format('d/m/Y H:i:s') ?? 'Nenhum retorno salvo' }}</div>
            </div>
            <div class="meta-actions">
                <a href="{{ route('admin.meta.embedded-signup.index') }}" class="btn btn-ghost">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Voltar
                </a>
                <button type="button" class="btn btn-primary" id="retryExchangeBtn" @if($migrationRequired || !request('code')) disabled @endif>
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Trocar code novamente
                </button>
            </div>
        </div>
    </section>

    <section class="card meta-col-4">
        <div class="meta-card-title">Sessão local</div>
        <div class="meta-card-subtitle">IDs recebidos via <code>postMessage</code> antes do redirect.</div>
        <div class="meta-stack">
            <div>
                <span class="meta-inline-label">WABA ID</span>
                <div class="meta-inline-value" id="storedWabaId">N/D</div>
            </div>
            <div>
                <span class="meta-inline-label">Phone Number ID</span>
                <div class="meta-inline-value" id="storedPhoneNumberId">N/D</div>
            </div>
            <div>
                <span class="meta-inline-label">Business ID</span>
                <div class="meta-inline-value" id="storedBusinessId">N/D</div>
            </div>
        </div>
    </section>

    <section class="meta-col-6">
        @include('meta.embedded-signup.partials.payload-card', [
            'title' => 'Query string do callback',
            'subtitle' => 'Parâmetros brutos presentes na URL de retorno.',
            'payload' => $callbackQuery,
            'badge' => request('code') ? 'code_received' : 'no_code',
        ])
    </section>

    <section class="meta-col-6">
        @include('meta.embedded-signup.partials.payload-card', [
            'title' => 'Último payload normalizado',
            'subtitle' => 'Último retorno salvo e processado pelo backend.',
            'payload' => $latestNormalizedPayload,
            'badge' => $latestSession?->connection_status,
        ])
    </section>

    <section class="meta-col-6">
        <div class="card">
            <div class="meta-card-title">Último payload bruto salvo</div>
            <div class="meta-card-subtitle">Último evento persistido no backend.</div>
            <pre class="json-block" id="latestRawPayloadBlock">{{ json_encode($latestRawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </section>

    <section class="meta-col-6">
        <div class="card">
            <div class="meta-card-title">Resposta da troca de code</div>
            <div class="meta-card-subtitle">Resultado do endpoint backend que troca <code>code</code> por token.</div>
            <pre class="json-block" id="exchangeResponseBlock">{}</pre>
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
        const exchangeResponseBlock = document.getElementById('exchangeResponseBlock');
        const latestRawPayloadBlock = document.getElementById('latestRawPayloadBlock');
        const latestStatus = document.getElementById('callbackLatestStatus');
        const latestEvent = document.getElementById('callbackLatestEvent');
        const latestAt = document.getElementById('callbackLatestAt');
        const retryExchangeBtn = document.getElementById('retryExchangeBtn');
        const storedWabaId = document.getElementById('storedWabaId');
        const storedPhoneNumberId = document.getElementById('storedPhoneNumberId');
        const storedBusinessId = document.getElementById('storedBusinessId');
        const query = new URLSearchParams(window.location.search);
        const callbackCode = query.get('code');

        const setFeedback = (message, type = '') => {
            callbackFeedback.textContent = message || '';
            callbackFeedback.className = `meta-feedback ${type}`.trim();
        };

        const formatJson = (value) => JSON.stringify(value ?? {}, null, 2);
        const readSessionData = () => {
            try {
                return JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
            } catch {
                return {};
            }
        };

        const populateStoredIds = () => {
            const sessionData = readSessionData();
            storedWabaId.textContent = sessionData.waba_id || 'N/D';
            storedPhoneNumberId.textContent = sessionData.phone_number_id || 'N/D';
            storedBusinessId.textContent = sessionData.business_id || 'N/D';
        };

        const refreshLatest = async () => {
            const response = await fetch(root.dataset.latestEndpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json().catch(() => ({}));
            const latest = data.latest;
            latestStatus.textContent = latest?.connection_status || data.config?.integration_status || 'N/D';
            latestEvent.textContent = latest?.event_type || 'N/D';
            latestAt.textContent = latest?.created_at ? new Date(latest.created_at).toLocaleString('pt-BR') : 'Nenhum retorno salvo';
            latestRawPayloadBlock.textContent = formatJson(latest?.raw_payload || {});
        };

        const exchangeCode = async () => {
            if (migrationRequired) {
                setFeedback('Execute php artisan migrate antes de usar esta área.', 'error');
                return;
            }

            if (!callbackCode) {
                setFeedback('Nenhum code encontrado na URL do callback.', 'error');
                return;
            }

            setFeedback('Enviando code para troca no backend...', '');
            exchangeResponseBlock.textContent = '{}';

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
            exchangeResponseBlock.textContent = formatJson(data);

            if (!response.ok) {
                throw new Error(data.message || 'Falha ao trocar code por token.');
            }

            await refreshLatest();
            setFeedback('Code processado com sucesso no backend.', 'success');
        };

        retryExchangeBtn?.addEventListener('click', async () => {
            try {
                await exchangeCode();
            } catch (error) {
                setFeedback(error.message || 'Falha ao processar callback.', 'error');
            }
        });

        populateStoredIds();

        if (!migrationRequired && callbackCode) {
            exchangeCode().catch((error) => {
                setFeedback(error.message || 'Falha ao processar callback.', 'error');
            });
        } else if (!callbackCode) {
            setFeedback('Abra esta URL a partir do fluxo do Embedded Signup para receber o code.', 'error');
        }
    })();
</script>
@endpush
