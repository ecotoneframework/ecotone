<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Api\Messaging\Message;

use function json_decode;
use function json_encode;

/**
 * licence Apache-2.0
 */
final class MessageDescription
{
    public static function of(Message $message): string
    {
        return json_encode([
            'payload' => $message->getPayload(),
            'headers' => json_decode((string)$message->getHeaders(), true),
        ]);
    }
}
