<?php

namespace Ecotone\EventSourcing\Prooph;

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;

class FromProophMessageToArrayConverter implements MessageConverter
{
    public function convertToArray(Message $domainMessage): array
    {
        return [
            'uuid' => $domainMessage->uuid(),
            'message_name' => $domainMessage->messageName(),
            'created_at' => $domainMessage->createdAt(),
            'payload' => $domainMessage->payload(),
            'metadata' => $domainMessage->metadata(),
        ];
    }
}
