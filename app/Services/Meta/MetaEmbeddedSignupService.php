<?php

namespace App\Services\Meta;

use App\DataTransferObjects\MetaEmbeddedSignupEventData;
use App\Models\MetaEmbeddedSignupConfig;
use App\Models\MetaEmbeddedSignupSession;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaEmbeddedSignupService
{
    public const DEFAULT_APP_ID = '1950946665537289';
    public const DEFAULT_CONFIGURATION_ID = '1666637874323961';
    public const DEFAULT_GRAPH_VERSION = 'v25.0';

    public function __construct(
        private readonly MetaEmbeddedSignupPayloadMapper $payloadMapper,
    ) {
    }

    public function storeSession(MetaEmbeddedSignupConfig $config, MetaEmbeddedSignupEventData $eventData): MetaEmbeddedSignupSession
    {
        $normalized = $this->payloadMapper->map($eventData->payload);

        $session = MetaEmbeddedSignupSession::create([
            'company_id' => $config->company_id,
            'meta_embedded_signup_config_id' => $config->id,
            'source' => $eventData->source,
            'event_type' => $normalized->eventType,
            'connection_status' => $normalized->connectionStatus,
            'waba_id' => $normalized->wabaId,
            'phone_number_id' => $normalized->phoneNumberId,
            'business_id' => $normalized->businessId,
            'display_name' => $normalized->displayName,
            'code' => $normalized->code,
            'access_token' => $normalized->accessToken,
            'setup_info' => $normalized->setupInfo,
            'raw_payload' => $eventData->payload,
            'normalized_payload' => $normalized->normalizedPayload,
            'meta_timestamp' => $normalized->metaTimestamp,
        ]);

        $config->update([
            'integration_status' => $normalized->connectionStatus ?? $config->integration_status ?? 'payload_received',
            'last_connected_at' => $session->created_at,
            'last_callback_at' => now(),
            'last_error' => null,
        ]);

        Log::info('Meta Embedded Signup payload persisted.', [
            'company_id' => $config->company_id,
            'session_id' => $session->id,
            'source' => $eventData->source,
            'event_type' => $normalized->eventType,
            'connection_status' => $normalized->connectionStatus,
        ]);

        return $session;
    }

    public function exchangeCodeForAccessToken(MetaEmbeddedSignupConfig $config, string $code, array $sessionContext = []): array
    {
        $response = Http::acceptJson()
            ->get(sprintf('https://graph.facebook.com/%s/oauth/access_token', $config->graph_api_version ?: self::DEFAULT_GRAPH_VERSION), [
                'client_id' => $config->facebook_app_id ?: self::DEFAULT_APP_ID,
                'client_secret' => $this->appSecret(),
                'code' => $code,
                'redirect_uri' => $config->redirect_uri,
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            Log::error('Meta Embedded Signup code exchange failed.', [
                'company_id' => $config->company_id,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            throw $exception;
        }

        $tokenPayload = $response->json();

        $this->storeSession($config, new MetaEmbeddedSignupEventData(
            payload: [
                'type' => 'WA_EMBEDDED_SIGNUP',
                'event' => 'CODE_EXCHANGE',
                'status' => 'token_exchanged',
                'code' => $code,
                'access_token' => $tokenPayload['access_token'] ?? null,
                'token_type' => $tokenPayload['token_type'] ?? null,
                'expires_in' => $tokenPayload['expires_in'] ?? null,
                'data' => $sessionContext,
                'timestamp' => now()->toIso8601String(),
            ],
            source: 'code_exchange',
        ));

        return $tokenPayload;
    }

    public function markConfigError(MetaEmbeddedSignupConfig $config, string $message, array $context = []): void
    {
        $config->update([
            'integration_status' => 'error',
            'last_error' => [
                'message' => $message,
                'context' => $context,
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);

        Log::warning('Meta Embedded Signup integration error.', [
            'company_id' => $config->company_id,
            'message' => $message,
            'context' => $context,
        ]);
    }

    public function systemUserToken(): ?string
    {
        return config('services.meta.system_user_token');
    }

    public function appSecret(): ?string
    {
        return config('services.meta.app_secret');
    }

    public function appId(): string
    {
        return (string) (config('services.meta.app_id') ?: self::DEFAULT_APP_ID);
    }

    public function configurationId(): string
    {
        return (string) (config('services.meta.configuration_id') ?: self::DEFAULT_CONFIGURATION_ID);
    }

    public function graphApiVersion(): string
    {
        return (string) (config('services.meta.graph_api_version') ?: self::DEFAULT_GRAPH_VERSION);
    }

    public function onboardingExtras(): array
    {
        return [
            'featureType' => 'whatsapp_business_app_onboarding',
            'sessionInfoVersion' => '3',
            'version' => 'v3',
            'features' => [
                ['name' => 'marketing_messages_lite'],
                ['name' => 'app_only_install'],
            ],
        ];
    }

    public function launchUrl(string $redirectUri): string
    {
        return 'https://business.facebook.com/messaging/whatsapp/onboard/?'.http_build_query([
            'app_id' => $this->appId(),
            'config_id' => $this->configurationId(),
            'extras' => json_encode($this->onboardingExtras(), JSON_UNESCAPED_SLASHES),
        ]);
    }
}
