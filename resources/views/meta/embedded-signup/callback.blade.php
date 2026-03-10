@extends('layouts.app')
@section('title', 'Callback Meta / Embedded Signup')

@php
    $rawPayload = $latestSession?->raw_payload ?? $callbackQuery;
    $normalizedPayload = $latestSession?->normalized_payload ?? [];
@endphp

@section('content')
<style>
    .callback-grid { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:16px; }
    .callback-col-4 { grid-column:span 4; }
    .callback-col-6 { grid-column:span 6; }
    .callback-col-12 { grid-column:span 12; }
    .json-block {
        background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:16px;
        font-size:12px; line-height:1.55; color:#b8c3ff; max-height:420px; overflow:auto; white-space:pre-wrap; word-break:break-word;
    }
    .callback-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
    .callback-kpi { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:18px; }
    .callback-kpi small {
        color:var(--muted); display:block; margin-bottom:8px; font-size:11px; text-transform:uppercase; letter-spacing:0.04em;
    }
    .callback-kpi strong { font-size:16px; }
    @media (max-width: 1100px) {
        .callback-col-4, .callback-col-6, .callback-col-12 { grid-column:span 12; }
    }
</style>

<div class="page-header">
    <h1>Callback Meta / Embedded Signup</h1>
    <p>Visualize o retorno mais recente, query params recebidos e o payload persistido no backend.</p>
</div>

@if($migrationRequired)
    <div class="alert alert-error">
        As tabelas da integração Meta ainda não existem neste banco. Execute <code>php artisan migrate</code> antes de usar o callback.
    </div>
@endif

<div id="metaCallbackApp"
     data-session-endpoint="{{ route('api.meta.embedded-signup.session.store') }}"
     data-latest-endpoint="{{ route('api.meta.embedded-signup.latest') }}"
     data-migration-required="{{ $migrationRequired ? '1' : '0' }}">
    <div class="callback-grid">
        <div class="callback-col-4 callback-kpi">
            <small>Status da conexão</small>
            <strong id="callbackStatus">{{ $latestSession?->connection_status ?? $config->integration_status ?? 'Aguardando retorno' }}</strong>
        </div>
        <div class="callback-col-4 callback-kpi">
            <small>Último retorno</small>
            <strong id="callbackTimestamp">{{ $latestSession?->created_at?->format('d/m/Y H:i:s') ?? 'Sem registros' }}</strong>
        </div>
        <div class="callback-col-4 callback-kpi">
            <small>Redirect URI configurada</small>
            <strong style="font-size:13px;word-break:break-word;">{{ $config->redirect_uri }}</strong>
        </div>

        <div class="card callback-col-12">
            <div class="callback-header">
                <div>
                    <div style="font-size:15px;font-weight:700;">Ações</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">O callback persiste automaticamente query params se houver code, token ou dados de setup.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-ghost" id="callbackReloadBtn">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Recarregar / sincronizar
                    </button>
                    <a href="{{ route('admin.meta.embedded-signup.index') }}" class="btn btn-primary">
                        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Voltar para integração
                    </a>
                </div>
            </div>
            <div id="callbackFeedback" style="font-size:12px;min-height:20px;"></div>
        </div>

        <div class="card callback-col-6">
            <div style="font-size:15px;font-weight:700;margin-bottom:14px;">Payload bruto formatado</div>
            <pre class="json-block" id="callbackRawBlock">{{ json_encode($rawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="card callback-col-6">
            <div style="font-size:15px;font-weight:700;margin-bottom:14px;">Dados extraídos</div>
            <pre class="json-block" id="callbackNormalizedBlock">{{ json_encode($normalizedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const root = document.getElementById('metaCallbackApp');
        if (!root) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const feedback = document.getElementById('callbackFeedback');
        const rawBlock = document.getElementById('callbackRawBlock');
        const normalizedBlock = document.getElementById('callbackNormalizedBlock');
        const callbackStatus = document.getElementById('callbackStatus');
        const callbackTimestamp = document.getElementById('callbackTimestamp');
        const reloadButton = document.getElementById('callbackReloadBtn');
        const migrationRequired = root.dataset.migrationRequired === '1';

        const setFeedback = (message, type = '') => {
            feedback.textContent = message || '';
            feedback.style.color = type === 'error' ? '#ff6584' : (type === 'success' ? '#43e97b' : 'var(--muted)');
        };

        if (migrationRequired) {
            setFeedback('Execute php artisan migrate antes de usar o callback da integração Meta.', 'error');
            return;
        }

        const persistCallbackQuery = async () => {
            const params = new URLSearchParams(window.location.search);
            if (![...params.keys()].length) {
                return;
            }

            const queryFingerprint = params.toString();
            if (window.sessionStorage.getItem('meta_embedded_signup_callback_query') === queryFingerprint) {
                return;
            }

            const payload = {
                type: 'WA_EMBEDDED_SIGNUP',
                source: 'callback_query',
                query: Object.fromEntries(params.entries()),
                code: params.get('code'),
                access_token: params.get('access_token'),
                status: params.get('status') || 'callback_received',
                data: {
                    business_id: params.get('business_id'),
                    waba_id: params.get('waba_id'),
                    phone_number_id: params.get('phone_number_id'),
                    setup_info: Object.fromEntries(params.entries())
                }
            };

            rawBlock.textContent = JSON.stringify(payload, null, 2);

            const response = await fetch(root.dataset.sessionEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ payload, source: 'callback_query' })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Falha ao persistir query string do callback.');
            }

            window.sessionStorage.setItem('meta_embedded_signup_callback_query', queryFingerprint);
            setFeedback('Callback salvo no backend.', 'success');
        };

        const reloadLatest = async () => {
            setFeedback('Sincronizando último retorno...', '');

            try {
                const response = await fetch(root.dataset.latestEndpoint, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                const latest = data.latest;

                rawBlock.textContent = JSON.stringify(latest?.raw_payload || {}, null, 2);
                normalizedBlock.textContent = JSON.stringify(latest?.normalized_payload || {}, null, 2);
                callbackStatus.textContent = latest?.connection_status || data.config?.integration_status || 'Sem status';
                callbackTimestamp.textContent = latest?.created_at ? new Date(latest.created_at).toLocaleString('pt-BR') : 'Sem registros';
                setFeedback('Último retorno atualizado.', 'success');
            } catch (error) {
                setFeedback(error.message || 'Falha ao sincronizar callback.', 'error');
            }
        };

        reloadButton?.addEventListener('click', reloadLatest);

        persistCallbackQuery()
            .catch((error) => setFeedback(error.message || 'Falha ao processar callback.', 'error'))
            .finally(reloadLatest);
    })();
</script>
@endpush
