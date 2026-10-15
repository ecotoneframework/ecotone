<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\MessagingException;

/**
 * Class WrongHandlerAmountException
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class WrongHandlerAmountException extends MessagingException
{
    public static function forDirectChannelSharedBy(string $channelName, string $subscribedHandler, string $anotherHandler): static
    {
        return static::create("Direct channel `{$channelName}` delivers a message to one handler only, yet both {$subscribedHandler} and {$anotherHandler} subscribe to it. Give one of them its own input channel, or register the channel with SimpleMessageChannelBuilder::createPublishSubscribeChannel('{$channelName}') so that each of them receives the message.");
    }

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::WRONG_HANDLER_AMOUNT_EXCEPTION;
    }
}
