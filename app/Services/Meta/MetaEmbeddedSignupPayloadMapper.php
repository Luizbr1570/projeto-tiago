<?php

namespace App\Services\Meta;

use App\DataTransferObjects\MetaEmbeddedSignupNormalizedData;
use Carbon\Carbon;

class MetaEmbeddedSignupPayloadMapper
{
    public function map(array $payload): MetaEmbeddedSignupNormalizedData
    {
        $eventType = $this->stringValue($payload, ['type', 'event', 'event_type']);
        $payloadData = $this->arrayValue($payload, ['data', 'payload', 'session_data']) ?? [];
        $setupInfo = $this->arrayValue($payloadData, ['setup_info', 'setupInfo', 'extras']);
        $code = $this->stringValue($payload, ['code']) ?? $this->stringValue($payloadData, ['code']);

        $wabaId = $this->stringValue($payloadData, ['waba_id', 'wabaId'])
            ?? $this->stringValue($setupInfo ?? [], ['waba_id', 'wabaId']);

        $phoneNumberId = $this->stringValue($payloadData, ['phone_number_id', 'phoneNumberId'])
            ?? $this->stringValue($setupInfo ?? [], ['phone_number_id', 'phoneNumberId']);

        $businessId = $this->stringValue($payloadData, ['business_id', 'businessId'])
            ?? $this->stringValue($setupInfo ?? [], ['business_id', 'businessId']);

        $displayName = $this->stringValue($payloadData, ['display_name', 'name'])
            ?? $this->stringValue($setupInfo ?? [], ['display_name', 'name']);

        $connectionStatus = $this->stringValue($payload, ['status', 'connection_status'])
            ?? $this->stringValue($payloadData, ['status', 'connection_status']);

        if (!$connectionStatus && $code) {
            $connectionStatus = 'code_received';
        }

        $metaTimestamp = $this->resolveTimestamp($payload, $payloadData);
        $accessToken = $this->stringValue($payload, ['access_token'])
            ?? $this->stringValue($payloadData, ['access_token']);

        $normalizedPayload = [
            'event_type' => $eventType,
            'connection_status' => $connectionStatus,
            'waba_id' => $wabaId,
            'phone_number_id' => $phoneNumberId,
            'business_id' => $businessId,
            'display_name' => $displayName,
            'code' => $code ?? null,
            'access_token' => $accessToken,
            'setup_info' => $setupInfo,
            'meta_timestamp' => $metaTimestamp?->toIso8601String(),
        ];

        return new MetaEmbeddedSignupNormalizedData(
            eventType: $eventType,
            connectionStatus: $connectionStatus,
            wabaId: $wabaId,
            phoneNumberId: $phoneNumberId,
            businessId: $businessId,
            displayName: $displayName,
            code: $code ?? null,
            accessToken: $accessToken,
            setupInfo: $setupInfo,
            metaTimestamp: $metaTimestamp,
            normalizedPayload: array_filter($normalizedPayload, fn ($value) => $value !== null)
        );
    }

    private function resolveTimestamp(array $payload, array $payloadData): ?Carbon
    {
        $timestamp = $this->stringValue($payload, ['timestamp', 'event_time', 'created_at'])
            ?? $this->stringValue($payloadData, ['timestamp', 'event_time', 'created_at']);

        if (!$timestamp) {
            return null;
        }

        if (ctype_digit($timestamp)) {
            $length = strlen($timestamp);

            return $length > 10
                ? Carbon::createFromTimestampMs((int) $timestamp)
                : Carbon::createFromTimestamp((int) $timestamp);
        }

        return Carbon::parse($timestamp);
    }

    private function arrayValue(array $payload, array $keys): ?array
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function stringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if ($value === null || $value === '') {
                continue;
            }

            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
