<?php

namespace App\Services\Meta;

use App\DataTransferObjects\MetaEmbeddedSignupEventData;
use App\Models\MetaEmbeddedSignupConfig;
use App\Models\MetaEmbeddedSignupSession;
use Illuminate\Support\Facades\Log;

class MetaEmbeddedSignupService
{
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
}
