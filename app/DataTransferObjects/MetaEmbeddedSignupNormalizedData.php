<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;

final class MetaEmbeddedSignupNormalizedData
{
    public function __construct(
        public readonly ?string $eventType,
        public readonly ?string $connectionStatus,
        public readonly ?string $wabaId,
        public readonly ?string $phoneNumberId,
        public readonly ?string $businessId,
        public readonly ?string $displayName,
        public readonly ?string $code,
        public readonly ?string $accessToken,
        public readonly ?array $setupInfo,
        public readonly ?CarbonInterface $metaTimestamp,
        public readonly array $normalizedPayload,
    ) {
    }
}
