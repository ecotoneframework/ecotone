<?php

declare(strict_types=1);

namespace Ecotone\Test;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Scheduling\NativeClock;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class StaticMessageBuilder
{
    public static function withPayload(mixed $payload): MessageBuilder
    {
        return self::builder()->withPayload($payload);
    }

    public static function fromMessage(Message $message): MessageBuilder
    {
        return self::builder()->fromMessage($message);
    }

    public static function fromParentMessage(Message $message): MessageBuilder
    {
        return self::builder()->fromParentMessage($message);
    }

    private static function builder(): MessageBuilder
    {
        return new MessageBuilder(new NativeClock());
    }
}
