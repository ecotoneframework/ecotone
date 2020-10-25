<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Message;

interface MessagingEntrypoint
{
    const ENTRYPOINT = "ecotone.messaging.entrypoint";

    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function send(#[Payload] $payload, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel);

    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function sendWithHeaders(#[Payload] $payload, #[Headers] array $headers, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel);

    /**
     * It must contain {MessagingEntrypoint::ENTRYPOINT} header
     */
    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function sendMessage(Message $message);
}