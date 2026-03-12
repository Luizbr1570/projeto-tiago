@extends('layouts.app')
@section('title', 'Meta / Embedded Signup')

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
    .meta-card-title { font-size:15px; font-weight:700; margin-bottom:4px; }
    .meta-card-subtitle { font-size:12px; color:var(--muted); margin-bottom:18px; }
    .meta-stack { display:grid; gap:14px; }
    .meta-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .meta-status {
        display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px;
        background:rgba(67,233,123,0.12); color:#43e97b; font-size:12px; font-weight:700;
    }
    .meta-status[data-tone="warning"] { background:rgba(255,193,7,0.12); color:#ffc107; }
    .meta-status[data-tone="danger"] { background:rgba(255,101,132,0.12); color:#ff6584; }
    .meta-kv { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
    .meta-inline-label {
        display:block; font-size:11px; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.04em;
    }
    .meta-inline-value { font-size:13px; font-weight:600; word-break:break-word; }
    .json-block {
        background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:16px;
        font-size:12px; line-height:1.55; color:#b8c3ff; max-height:360px; overflow:auto; white-space:pre-wrap; word-break:break-word;
    }
    .empty-state {
        border:1px dashed var(--border); border-radius:10px; padding:18px; color:var(--muted); text-align:center; font-size:13px;
    }
    .meta-feedback { min-height:20px; font-size:12px; }
    .meta-feedback.error { color:#ff6584; }
    .meta-feedback.success { color:#43e97b; }
    .meta-mini { font-size:11px; color:var(--muted); margin-top:8px; line-height:1.5; }
    .meta-link {
        display:block;
        color:#8db0ff;
        text-decoration:none;
        word-break:break-all;
        font-size:12px;
        line-height:1.5;
    }
    .meta-link:hover { color:#bfd2ff; }
    .meta-table-actions button {
        background:transparent; border:1px solid var(--border); color:var(--text); border-radius:6px; padding:6px 10px; cursor:pointer; font-size:12px;
    }
    .meta-table-actions button:hover { border-color:var(--accent); color:var(--accent); }
    @media (max-width: 1100px) {
        .meta-col-4, .meta-col-6, .meta-col-12 { grid-column:span 12; }
        .meta-kv { grid-template-columns:1fr; }
    }
</style>

<div class="page-header">
    <h1>Meta / Embedded Signup</h1>
    <p>Fluxo configurado no backend com WhatsApp Business App Onboarding, sem edição inline das variáveis sensíveis.</p>
</div>

@if($migrationRequired)
    <div class="alert alert-error">
        As tabelas da integração Meta ainda não existem neste banco. Execute <code>php artisan migrate</code> antes de usar esta área.
    </div>
@endif

<div class="meta-grid" id="metaEmbeddedSignupApp"
     data-latest-endpoint="{{ route('api.meta.embedded-signup.latest') }}"
     data-session-endpoint="{{ route('api.meta.embedded-signup.session.store') }}"
     data-exchange-endpoint="{{ route('api.meta.embedded-signup.exchange-code') }}"
     data-sessions-endpoint="{{ route('api.meta.embedded-signup.sessions') }}"
     data-migration-required="{{ $migrationRequired ? '1' : '0' }}">

    <section class="card meta-col-4">
        <div class="meta-card-title">Configuração do backend</div>
        <div class="meta-card-subtitle">Os parâmetros do onboarding vêm do backend e não são editados nesta tela.</div>

        <div class="meta-stack">
            <div>
                <span class="meta-inline-label">App ID</span>
                <div class="meta-inline-value">{{ $config->facebook_app_id }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Configuration ID</span>
                <div class="meta-inline-value">{{ $config->configuration_id }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Graph API Version</span>
                <div class="meta-inline-value">{{ $config->graph_api_version }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Callback URL</span>
                <div class="meta-inline-value">{{ $config->redirect_uri }}</div>
            </div>
            <div class="meta-mini">
                App secret: <strong>{{ $metaAppSecretConfigured ? 'configurado' : 'não configurado' }}</strong><br>
                System user token: <strong>{{ $metaSystemTokenConfigured ? 'configurado' : 'não configurado' }}</strong>
            </div>
        </div>
    </section>

    <section class="card meta-col-4">
        <div class="meta-card-title">Conexão</div>
        <div class="meta-card-subtitle">Fluxo oficial com <code>whatsapp_business_app_onboarding</code> e coleta de <code>code</code>, <code>waba_id</code> e <code>phone_number_id</code>.</div>

        @php
            $status = $config->integration_status ?: 'not_configured';
            $tone = str_contains($status, 'error') ? 'danger' : ($status === 'not_configured' ? 'warning' : 'success');
        @endphp

        <div class="meta-stack">
            <div class="meta-status" data-tone="{{ $tone }}">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                <span id="metaIntegrationStatus">{{ $status }}</span>
            </div>

            <div>
                <span class="meta-inline-label">Link gerado</span>
                <a class="meta-link" href="{{ $launchUrl }}" target="_blank" rel="noopener noreferrer">{{ $launchUrl }}</a>
            </div>

            <div class="meta-actions">
                <button type="button" class="btn btn-primary" id="startEmbeddedSignupBtn" @if($migrationRequired) disabled @endif>
                    <i data-lucide="plug-zap" style="width:14px;height:14px;"></i> Login with Facebook
                </button>
                <a href="{{ $launchUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">
                    <i data-lucide="external-link" style="width:14px;height:14px;"></i> Abrir link direto
                </a>
            </div>

            <div id="sdkFeedback" class="meta-feedback"></div>
        </div>
    </section>

    <section class="card meta-col-4">
        <div class="meta-card-title">Último retorno</div>
        <div class="meta-card-subtitle">Resumo do último evento ou troca de token persistida no backend.</div>

        <div class="meta-stack">
            <div>
                <span class="meta-inline-label">Último retorno salvo</span>
                <div class="meta-inline-value" id="latestReturnAt">{{ $latestSession?->created_at?->format('d/m/Y H:i:s') ?? 'Nenhum retorno salvo' }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Status</span>
                <div class="meta-inline-value" id="latestReturnStatus">{{ $latestSession?->connection_status ?? 'Aguardando callback' }}</div>
            </div>
            <div>
                <span class="meta-inline-label">Evento</span>
                <div class="meta-inline-value" id="latestReturnEvent">{{ $latestSession?->event_type ?? 'N/D' }}</div>
            </div>
            <div class="meta-actions">
                <button type="button" class="btn btn-ghost" id="refreshLatestBtn">
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Recarregar / sincronizar
                </button>
            </div>
            <div id="refreshFeedback" class="meta-feedback"></div>
        </div>
    </section>

    <section class="meta-col-6">
        @include('meta.embedded-signup.partials.payload-card', [
            'title' => 'Payload bruto recebido',
            'subtitle' => 'Conteúdo persistido do último postMessage ou callback.',
            'payload' => $latestRawPayload,
            'badge' => $latestSession?->source,
        ])
    </section>

    <section class="meta-col-6">
        @include('meta.embedded-signup.partials.payload-card', [
            'title' => 'Dados extraídos',
            'subtitle' => 'Inclui code, access token, WABA ID e Phone Number ID.',
            'payload' => $latestNormalizedPayload,
            'badge' => $latestSession?->connection_status,
        ])
    </section>

    <section class="meta-col-12">
        <div class="meta-kv">
            <div class="card">
                <span class="meta-inline-label">Session info response</span>
                <pre class="json-block" id="livePayloadBlock">{{ json_encode($latestRawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            <div class="card">
                <span class="meta-inline-label">SDK response</span>
                <pre class="json-block" id="sdkResponseBlock">{}</pre>
            </div>
        </div>
    </section>

    <section class="card meta-col-12">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;">
            <div>
                <div class="meta-card-title">Números conectados</div>
                <div class="meta-card-subtitle" style="margin-bottom:0;">Registros persistidos do signup com os identificadores normalizados.</div>
            </div>
        </div>

        <div style="overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>WABA ID</th>
                        <th>Phone Number ID</th>
                        <th>Business ID</th>
                        <th>Nome / Status</th>
                        <th>Data da conexão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="connectedNumbersTable">
                    @forelse($connectedNumbers as $session)
                        <tr>
                            <td>{{ $session->waba_id ?? 'N/D' }}</td>
                            <td>{{ $session->phone_number_id ?? 'N/D' }}</td>
                            <td>{{ $session->business_id ?? 'N/D' }}</td>
                            <td>{{ $session->display_name ?? $session->connection_status ?? 'N/D' }}</td>
                            <td>{{ $session->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="meta-table-actions">
                                <button type="button" data-session='@json($session->raw_payload)'>Detalhes</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--muted);">Nenhum número conectado ainda.</td>
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
        launchUrl: @json($launchUrl),
        onboardingExtras: @json($onboardingExtras),
    };

    window.fbAsyncInit = function () {
        FB.init({
            appId: window.metaEmbeddedSignupConfig.appId,
            autoLogAppEvents: true,
            xfbml: true,
            version: window.metaEmbeddedSignupConfig.version || 'v25.0'
        });
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
        const refreshFeedback = document.getElementById('refreshFeedback');
        const livePayloadBlock = document.getElementById('livePayloadBlock');
        const sdkResponseBlock = document.getElementById('sdkResponseBlock');
        const integrationStatus = document.getElementById('metaIntegrationStatus');
        const latestReturnAt = document.getElementById('latestReturnAt');
        const latestReturnStatus = document.getElementById('latestReturnStatus');
        const latestReturnEvent = document.getElementById('latestReturnEvent');
        const connectedNumbersTable = document.getElementById('connectedNumbersTable');
        const migrationRequired = root.dataset.migrationRequired === '1';

        const setFeedback = (target, message, type = '') => {
            target.textContent = message || '';
            target.className = `meta-feedback ${type}`.trim();
        };

        if (migrationRequired) {
            setFeedback(sdkFeedback, 'Execute php artisan migrate antes de usar a integração Meta.', 'error');
            setFeedback(refreshFeedback, 'Migrações pendentes.', 'error');
            return;
        }

        const formatJson = (value) => JSON.stringify(value ?? {}, null, 2);
        const storeSessionData = (value) => window.sessionStorage.setItem(storageKey, JSON.stringify(value ?? {}));
        const readSessionData = () => {
            try {
                return JSON.parse(window.sessionStorage.getItem(storageKey) || '{}');
            } catch {
                return {};
            }
        };

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
                throw new Error(data.message || 'Falha ao persistir payload da Meta.');
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
                throw new Error(data.message || 'Falha ao trocar code por token.');
            }

            return data;
        };

        const escapeAttribute = (value) => String(value).replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;');

        const renderSessions = async () => {
            const response = await fetch(root.dataset.sessionsEndpoint, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();
            const rows = data.data || [];

            if (!rows.length) {
                connectedNumbersTable.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);">Nenhum número conectado ainda.</td></tr>';
                return;
            }

            connectedNumbersTable.innerHTML = rows.map((session) => {
                const payload = escapeAttribute(JSON.stringify(session.raw_payload || {}));
                return `
                    <tr>
                        <td>${session.waba_id || 'N/D'}</td>
                        <td>${session.phone_number_id || 'N/D'}</td>
                        <td>${session.business_id || 'N/D'}</td>
                        <td>${session.display_name || session.connection_status || 'N/D'}</td>
                        <td>${session.created_at ? new Date(session.created_at).toLocaleString('pt-BR') : 'N/D'}</td>
                        <td class="meta-table-actions">
                            <button type="button" data-session='${payload}'>Detalhes</button>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const refreshLatest = async () => {
            setFeedback(refreshFeedback, 'Atualizando último retorno...', '');

            try {
                const response = await fetch(root.dataset.latestEndpoint, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                const latest = data.latest;

                integrationStatus.textContent = data.config?.integration_status || 'N/D';
                latestReturnAt.textContent = latest?.created_at ? new Date(latest.created_at).toLocaleString('pt-BR') : 'Nenhum retorno salvo';
                latestReturnStatus.textContent = latest?.connection_status || 'Aguardando callback';
                latestReturnEvent.textContent = latest?.event_type || 'N/D';
                livePayloadBlock.textContent = formatJson(latest?.raw_payload || {});

                await renderSessions();
                setFeedback(refreshFeedback, 'Dados sincronizados.', 'success');
            } catch (error) {
                setFeedback(refreshFeedback, error.message || 'Falha ao sincronizar.', 'error');
            }
        };

        const parseMetaMessage = (rawData) => typeof rawData === 'string' ? JSON.parse(rawData) : rawData;

        window.addEventListener('message', async (event) => {
            if (event.origin !== 'https://www.facebook.com' && event.origin !== 'https://web.facebook.com') {
                return;
            }

            try {
                const data = parseMetaMessage(event.data);
                if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') {
                    return;
                }

                livePayloadBlock.textContent = formatJson(data);

                if (data.event === 'FINISH') {
                    storeSessionData(data.data || {});
                }

                await postPayload(data, 'post_message');
                await refreshLatest();

                if (data.event === 'FINISH') {
                    setFeedback(sdkFeedback, 'Onboarding concluído. IDs salvos localmente e no backend.', 'success');
                } else if (data.event === 'CANCEL') {
                    setFeedback(sdkFeedback, `Fluxo cancelado em ${data.data?.current_step || 'etapa desconhecida'}.`, 'error');
                } else if (data.event === 'ERROR') {
                    setFeedback(sdkFeedback, data.data?.error_message || 'Erro reportado pela Meta.', 'error');
                }
            } catch (error) {
                setFeedback(sdkFeedback, error.message || 'Falha ao processar evento da Meta.', 'error');
            }
        });

        const fbLoginCallback = async (response) => {
            sdkResponseBlock.textContent = formatJson(response || {});

            if (!response?.authResponse?.code) {
                setFeedback(sdkFeedback, 'Fluxo encerrado sem code de autorização.', 'error');
                return;
            }

            try {
                await exchangeCode(response.authResponse.code);
                await refreshLatest();
                setFeedback(sdkFeedback, 'Code recebido e enviado ao backend para troca por token.', 'success');
            } catch (error) {
                setFeedback(sdkFeedback, error.message || 'Falha ao enviar code ao backend.', 'error');
            }
        };

        const launchWhatsAppSignup = () => {
            setFeedback(sdkFeedback, '', '');

            if (!window.FB) {
                setFeedback(sdkFeedback, 'SDK do Facebook ainda não carregou. Abrindo link direto como fallback.', 'error');
                window.open(window.metaEmbeddedSignupConfig.launchUrl, '_blank', 'noopener,noreferrer');
                return;
            }

            FB.login(fbLoginCallback, {
                config_id: '1666637874323961',
                response_type: 'code',
                override_default_response_type: true,
                extras: window.metaEmbeddedSignupConfig.onboardingExtras
            });
        };

        startButton?.addEventListener('click', launchWhatsAppSignup);
        refreshButton?.addEventListener('click', refreshLatest);

        connectedNumbersTable?.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-session]');
            if (!button) {
                return;
            }

            try {
                const payload = JSON.parse(button.dataset.session || '{}');
                livePayloadBlock.textContent = formatJson(payload);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                setFeedback(refreshFeedback, 'Não foi possível abrir os detalhes do payload.', 'error');
            }
        });
    })();
</script>
@endpush
