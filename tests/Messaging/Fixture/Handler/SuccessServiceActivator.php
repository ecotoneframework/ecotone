<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class SuccessServiceActivator
{
    private int $calls = 0;
    private Message $lastCalledMessage;

    #[Asynchronous('async_channel')]
    #[InternalHandler('handle_channel', endpointId: 'success_service_activator')]
    public function handle(Message $message): void
    {
        $this->lastCalledMessage = $message;
        $this->calls++;
    }

    #[QueryHandler('get_number_of_calls')]
    public function getNumberOfCalls(): int
    {
        return $this->calls;
    }

    #[QueryHandler('get_last_message_headers')]
    public function getLastCalledMessage(): array
    {
        return $this->lastCalledMessage->getHeaders()->headers();
    }

    public function __toString()
    {
        return self::class;
    }
}
