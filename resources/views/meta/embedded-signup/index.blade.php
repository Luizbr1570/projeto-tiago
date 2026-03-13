@extends('layouts.app')
@section('title', 'Login Meta')

@section('content')
<style>
    .meta-grid { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:16px; }
    .meta-col-4 { grid-column:span 4; }
    .meta-col-6 { grid-column:span 6; }
    .meta-col-8 { grid-column:span 8; }
    .meta-col-12 { grid-column:span 12; }
    .meta-stack { display:grid; gap:14px; }
    .meta-card-title { font-size:15px; font-weight:700; margin-bottom:4px; }
    .meta-card-subtitle { font-size:12px; color:var(--muted); margin-bottom:18px; }
    .meta-status {
        display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
        background:rgba(67,233,123,0.12); color:#43e97b; font-size:12px; font-weight:700;
    }
    .meta-status[data-tone="warning"] { background:rgba(255,193,7,0.12); color:#ffc107; }
    .meta-status[data-tone="danger"] { background:rgba(255,101,132,0.12); color:#ff6584; }
    .meta-feedback { min-height:20px; font-size:12px; }
    .meta-feedback.error { color:#ff6584; }
    .meta-feedback.success { color:#43e97b; }
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
    .meta-empty {
        border:1px dashed var(--border); border-radius:10px; padding:18px; color:var(--muted); text-align:center; font-size:13px;
    }
    .meta-actions { display:flex; gap:10px; flex-wrap:wrap; }
    @media (max-width: 1100px) {
        .meta-col-4, .meta-col-6, .meta-col-8, .meta-col-12 { grid-column:span 12; }
        .meta-summary { grid-template-columns:1fr; }
    }
</style>

<div class="page-header">
    <h1>Login Meta</h1>
    <p>Conecte sua conta do WhatsApp Business e acompanhe apenas as informacoes principais da conexao.</p>
</div>

@if($migrationRequired)
    <div class="alert alert-error">
        As tabelas da integracao Meta ainda nao existem neste banco. Execute <code>php artisan migrate</code> antes de usar esta area.
    </div>
@endif

@php
    $status = $config->integration_status ?: 'not_configured';
    $tone = str_contains($status, 'error') ? 'danger' : ($status === 'not_configured' ? 'warning' : 'success');
@endphp

<div class="meta-grid" id="metaEmbeddedSignupApp"
     data-latest-endpoint="{{ route('api.meta.embedded-signup.latest') }}"
     data-session-endpoint="{{ route('api.meta.embedded-signup.session.store') }}"
     data-exchange-endpoint="{{ route('api.meta.embedded-signup.exchange-code') }}"
     data-sessions-endpoint="{{ route('api.meta.embedded-signup.sessions') }}"
     data-migration-required="{{ $migrationRequired ? '1' : '0' }}">

    <section class="card meta-col-4">
        <div class="meta-card-title">Conexao</div>
        <div class="meta-card-subtitle">Inicie o login oficial da Meta para conectar seu numero.</div>

        <div class="meta-stack">
            <div class="meta-status" data-tone="{{ $tone }}">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                <span id="metaIntegrationStatus">{{ $status }}</span>
            </div>

            <div class="meta-actions">
                <button type="button" class="btn btn-primary" id="startEmbeddedSignupBtn" @if($migrationRequired) disabled @endif>
                    <i data-lucide="log-in" style="width:14px;height:14px;"></i> Login com Facebook
                </button>
                <button type="button" class="btn btn-ghost" id="refreshLatestBtn">
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Atualizar
                </button>
            </div>

            <div id="sdkFeedback" class="meta-feedback"></div>
        </div>
    </section>

    <section class="card meta-col-8">
        <div class="meta-card-title">Ultimo login</div>
        <div class="meta-card-subtitle">Dados retornados apos a conexao mais recente.</div>

        <div class="meta-summary" id="latestConnectionSummary">
            <div class="meta-summary-item">
                <span class="meta-inline-label">Nome</span>
                <div class="meta-inline-value" id="latestDisplayName">{{ $latestSession?->display_name ?? 'Nao conectado' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Status</span>
                <div class="meta-inline-value" id="latestReturnStatus">{{ $latestSession?->connection_status ?? 'Aguardando login' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">WhatsApp Business ID</span>
                <div class="meta-inline-value" id="latestWabaId">{{ $latestSession?->waba_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Numero ID</span>
                <div class="meta-inline-value" id="latestPhoneNumberId">{{ $latestSession?->phone_number_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Business ID</span>
                <div class="meta-inline-value" id="latestBusinessId">{{ $latestSession?->business_id ?? 'N/D' }}</div>
            </div>
            <div class="meta-summary-item">
                <span class="meta-inline-label">Conectado em</span>
                <div class="meta-inline-value" id="latestReturnAt">{{ $latestSession?->created_at?->format('d/m/Y H:i:s') ?? 'Nenhum login realizado' }}</div>
            </div>
        </div>
    </section>

    <section class="card meta-col-12">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;">
            <div>
                <div class="meta-card-title">Contas conectadas</div>
                <div class="meta-card-subtitle" style="margin-bottom:0;">Historico simples dos numeros conectados.</div>
            </div>
        </div>

        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>WhatsApp Business ID</th>
                        <th>Numero ID</th>
                        <th>Business ID</th>
                        <th>Data da conexao</th>
                    </tr>
                </thead>
                <tbody id="connectedNumbersTable">
                    @forelse($connectedNumbers as $session)
                        <tr>
                            <td>{{ $session->display_name ?? 'N/D' }}</td>
                            <td>{{ $session->connection_status ?? 'N/D' }}</td>
                            <td>{{ $session->waba_id ?? 'N/D' }}</td>
                            <td>{{ $session->phone_number_id ?? 'N/D' }}</td>
                            <td>{{ $session->business_id ?? 'N/D' }}</td>
                            <td>{{ $session->created_at?->format('d/m/Y H:i:s') ?? 'N/D' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--muted);">Nenhuma conta conectada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    window.metaEmbeddedSignupConfig = {
        appId: @json($config->facebook_app_id),
        version: @json($config->graph_api_version ?: 'v25.0'),
        configurationId: @json($config->configuration_id),
        redirectUri: @json($config->redirect_uri),
        onboardingExtras: @json($onboardingExtras),
    };

    window.fbAsyncInit = function () {
        FB.init({
            appId: window.metaEmbeddedSignupConfig.appId,
            autoLogAppEvents: true,
            xfbml: true,
            version: window.metaEmbeddedSignupConfig.version || 'v25.0'
        });

        window.dispatchEvent(new Event('meta-facebook-sdk-ready'));
    };

    (function (d, s, id) {
        const firstScript = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }

        const script = d.createElement(s);
        script.id = id;
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        script.async = true;
        script.defer = true;
        script.crossOrigin = 'anonymous';
        firstScript.parentNode.insertBefore(script, firstScript);
    }(document, 'script', 'facebook-jssdk'));

    (() => {
        const root = document.getElementById('metaEmbeddedSignupApp');
        if (!root) {
            return;
        }

        const storageKey = 'meta_embedded_signup_session_data';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const startButton = document.getElementById('startEmbeddedSignupBtn');
        const refreshButton = document.getElementById('refreshLatestBtn');
        const sdkFeedback = document.getElementById('sdkFeedback');
        const integrationStatus = document.getElementById('metaIntegrationStatus');
        const latestDisplayName = document.getElementById('latestDisplayName');
        const latestReturnAt = document.getElementById('latestReturnAt');
        const latestReturnStatus = document.getElementById('latestReturnStatus');
        const latestWabaId = document.getElementById('latestWabaId');
        const latestPhoneNumberId = document.getElementById('latestPhoneNumberId');
        const latestBusinessId = document.getElementById('latestBusinessId');
        const connectedNumbersTable = document.getElementById('connectedNumbersTable');
        const migrationRequired = root.dataset.migrationRequired === '1';
        let isSdkReady = typeof window.FB !== 'undefined';

        const setFeedback = (target, message, type = '') => {
            target.textContent = message || '';
            target.className = `meta-feedback ${type}`.trim();
        };

        const storeSessionData = (value) => window.sessionStorage.setItem(storageKey, JSON.stringify(value ?? {}));
        const readSessionData = () => {
            try {
                return JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
            } catch {
                return {};
            }
        };

        const syncLatestCard = (latest, config) => {
            integrationStatus.textContent = config?.integration_status || latest?.connection_status || 'N/D';
            latestDisplayName.textContent = latest?.display_name || 'Nao conectado';
            latestReturnAt.textContent = latest?.created_at ? new Date(latest.created_at).toLocaleString('pt-BR') : 'Nenhum login realizado';
            latestReturnStatus.textContent = latest?.connection_status || 'Aguardando login';
            latestWabaId.textContent = latest?.waba_id || 'N/D';
            latestPhoneNumberId.textContent = latest?.phone_number_id || 'N/D';
            latestBusinessId.textContent = latest?.business_id || 'N/D';
        };

        if (migrationRequired) {
            setFeedback(sdkFeedback, 'Execute php artisan migrate antes de usar a integracao Meta.', 'error');
            return;
        }

        const syncSdkState = () => {
            isSdkReady = typeof window.FB !== 'undefined';
            startButton.disabled = migrationRequired;

            if (!isSdkReady && !sdkFeedback.textContent) {
                setFeedback(sdkFeedback, 'Carregando login da Meta...', '');
            }
        };

        syncSdkState();

        window.addEventListener('meta-facebook-sdk-ready', () => {
            isSdkReady = true;
            startButton.disabled = false;
            setFeedback(sdkFeedback, '', '');
        });

        const sdkPolling = window.setInterval(() => {
            if (typeof window.FB !== 'undefined') {
                isSdkReady = true;
                startButton.disabled = false;
                window.clearInterval(sdkPolling);
            }
        }, 500);

        window.setTimeout(() => window.clearInterval(sdkPolling), 10000);

        const postPayload = async (payload, source) => {
            const response = await fetch(root.dataset.sessionEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ payload, source })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Falha ao salvar os dados do login Meta.');
            }

            return data;
        };

        const exchangeCode = async (code) => {
            const response = await fetch(root.dataset.exchangeEndpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code,
                    session_data: readSessionData()
                })
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Falha ao concluir o login Meta.');
            }

            return data;
        };

        const renderSessions = async () => {
            const response = await fetch(root.dataset.sessionsEndpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json().catch(() => ({}));
            const rows = data.data || [];

            if (!rows.length) {
                connectedNumbersTable.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);">Nenhuma conta conectada ainda.</td></tr>';
                return;
            }

            connectedNumbersTable.innerHTML = rows.map((session) => `
                <tr>
                    <td>${session.display_name || 'N/D'}</td>
                    <td>${session.connection_status || 'N/D'}</td>
                    <td>${session.waba_id || 'N/D'}</td>
                    <td>${session.phone_number_id || 'N/D'}</td>
                    <td>${session.business_id || 'N/D'}</td>
                    <td>${session.created_at ? new Date(session.created_at).toLocaleString('pt-BR') : 'N/D'}</td>
                </tr>
            `).join('');
        };

        const refreshLatest = async (message = 'Informacoes atualizadas com sucesso.') => {
            const response = await fetch(root.dataset.latestEndpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json().catch(() => ({}));

            syncLatestCard(data.latest, data.config);
            await renderSessions();
            setFeedback(sdkFeedback, message, 'success');
        };

        const parseMetaMessage = (rawData) => typeof rawData === 'string' ? JSON.parse(rawData) : rawData;

        window.addEventListener('message', (event) => {
            if (event.origin !== 'https://www.facebook.com' && event.origin !== 'https://web.facebook.com') {
                return;
            }

            void (async () => {
                try {
                    const data = parseMetaMessage(event.data);
                    if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') {
                        return;
                    }

                    if (data.event === 'FINISH') {
                        storeSessionData(data.data || {});
                    }

                    await postPayload(data, 'post_message');

                    if (data.event === 'FINISH') {
                        await refreshLatest('Login Meta concluido com sucesso.');
                        return;
                    }

                    if (data.event === 'CANCEL') {
                        setFeedback(sdkFeedback, 'O login foi cancelado antes da conclusao.', 'error');
                        return;
                    }

                    if (data.event === 'ERROR') {
                        setFeedback(sdkFeedback, data.data?.error_message || 'A Meta informou um erro ao concluir o login.', 'error');
                    }
                } catch (error) {
                    setFeedback(sdkFeedback, error.message || 'Falha ao processar o retorno do login Meta.', 'error');
                }
            })();
        });

        const fbLoginCallback = (response) => {
            if (!response?.authResponse?.code) {
                setFeedback(sdkFeedback, 'O login foi encerrado sem autorizacao.', 'error');
                return;
            }

            void (async () => {
                try {
                    await exchangeCode(response.authResponse.code);
                    await refreshLatest('Conta conectada com sucesso.');
                } catch (error) {
                    setFeedback(sdkFeedback, error.message || 'Falha ao concluir o login Meta.', 'error');
                }
            })();
        };

        const launchWhatsAppSignup = () => {
            setFeedback(sdkFeedback, '', '');

            if (!window.metaEmbeddedSignupConfig.appId) {
                setFeedback(sdkFeedback, 'META_APP_ID nao foi configurado.', 'error');
                return;
            }

            if (!window.metaEmbeddedSignupConfig.configurationId) {
                setFeedback(sdkFeedback, 'META_CONFIGURATION_ID nao foi configurado.', 'error');
                return;
            }

            if (!isSdkReady || !window.FB || typeof window.FB.login !== 'function') {
                syncSdkState();
                setFeedback(sdkFeedback, 'O login da Meta ainda nao terminou de carregar. Tente novamente em instantes.', 'error');
                return;
            }

            try {
                window.FB.login(fbLoginCallback, {
                    config_id: window.metaEmbeddedSignupConfig.configurationId,
                    response_type: 'code',
                    override_default_response_type: true,
                    extras: window.metaEmbeddedSignupConfig.onboardingExtras
                });
            } catch (error) {
                setFeedback(sdkFeedback, error.message || 'Nao foi possivel abrir o login da Meta.', 'error');
            }
        };

        window.launchWhatsAppSignup = launchWhatsAppSignup;
        startButton?.addEventListener('click', launchWhatsAppSignup);
        refreshButton?.addEventListener('click', async () => {
            try {
                await refreshLatest();
            } catch (error) {
                setFeedback(sdkFeedback, error.message || 'Falha ao atualizar as informacoes.', 'error');
            }
        });
    })();
</script>
@endpush
