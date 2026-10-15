<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer\PollOrThrow;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHandler;

/**
 * Class PollingConsumer
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PollOrThrowExceptionConsumer implements EndpointRunner
{
    public function __construct(private PollableChannel $pollableChannel, private MessageHandler $messageHandler)
    {
    }

    public static function createWithoutName(PollableChannel $pollableChannel, MessageHandler $messageHandler): self
    {
        return new self($pollableChannel, $messageHandler);
    }

    public static function create(PollableChannel $pollableChannel, MessageHandler $messageHandler): self
    {
        return new self($pollableChannel, $messageHandler);
    }

    public function runEndpointWithExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $message = $this->pollableChannel->receive();
        if (is_null($message)) {
            throw MessageDeliveryException::create('Message was not delivered to ' . self::class);
        }

        $this->messageHandler->handle($message);
    }
}
