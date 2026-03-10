<?php

namespace App\DataTransferObjects;

final class MetaEmbeddedSignupEventData
{
    public function __construct(
        public readonly array $payload,
        public readonly string $source = 'embedded_signup',
    ) {
    }
}
