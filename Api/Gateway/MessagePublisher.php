<?php

declare(strict_types=1);

namespace Ecotone\Api\Gateway;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Messaging\Future;

/**
 * licence Apache-2.0
 */
interface MessagePublisher
{
    public function send(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN): void;

    public function sendWithMetadata(string $data, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []): void;

    public function convertAndSend(object|array $data): void;

    public function convertAndSendWithMetadata(object|array $data, array $metadata): void;

    /**
     * @link https://docs.ecotone.tech/modelling/asynchronous-handling/non-blocking-batched-delivery#publishing-with-futures
     */
    public function publishDeferred(mixed $data, string $sourceMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): Future;
}
