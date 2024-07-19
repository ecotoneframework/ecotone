<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

/**
 * licence Apache-2.0
 */
interface StringReturningGateway
{
    public function execute(string $replyMediaType): string;

    public function executeWithPayload(mixed $payload, string $replyMediaType): string;

    public function executeWithPayloadAndHeaders(mixed $payload, array $headers, string $replyMediaType): string;

    public function executeWithDefault(mixed $payload = 'default'): string;

    public function executeNoParams(): string;
}
