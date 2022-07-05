<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Message;

interface MessagingEntrypoint
{
    const ENTRYPOINT = "ecotone.messaging.entrypoint";

    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function send(#[Payload] $payload, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel) : mixed;

    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function sendWithHeaders(#[Payload] $payload, #[Headers] array $headers, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel) : mixed;

    /**
     * It must contain {MessagingEntrypoint::ENTRYPOINT} header
     */
    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function sendMessage(Message $message) : mixed;
}