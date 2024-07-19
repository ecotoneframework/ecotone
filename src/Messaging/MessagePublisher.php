<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
interface MessagePublisher
{
    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN): void;

    public function sendWithMetadata(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []): void;

    public function convertAndSend(object|array $data): void;

    public function convertAndSendWithMetadata(object|array $data, array $metadata): void;
}
