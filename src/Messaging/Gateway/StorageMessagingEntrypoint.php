<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;

/**
 * licence Apache-2.0
 */
class StorageMessagingEntrypoint implements MessagingEntrypoint
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function send(#[Payload] $payload, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel): mixed
    {
        return null;
    }

    public function sendWithHeaders(#[Payload] $payload, #[Headers] array $headers, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel, #[Header(MessageHeaders::ROUTING_SLIP)] ?string $routingSlip = null): mixed
    {
        return null;
    }

    public function sendMessage(Message $message): mixed
    {
        return null;
    }

    public function sendWithHeadersWithMessageReply(#[Payload] $payload, #[Headers] array $headers, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel, #[Header(MessageHeaders::ROUTING_SLIP)] ?string $routingSlip = null): ?Message
    {
        return null;
    }
}
