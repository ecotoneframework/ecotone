<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Message;

interface ConsoleCommandRunner
{
    #[MessageGateway(MessagingEntrypoint::ENTRYPOINT)]
    public function execute(#[Payload] $payload, #[Headers] array $headers, #[Header(MessagingEntrypoint::ENTRYPOINT)] string $targetChannel) : mixed;
}