<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class SuccessServiceActivator
{
    private int $calls = 0;
    private Message $lastCalledMessage;

    #[Asynchronous('async_channel')]
    #[ServiceActivator('handle_channel', 'success_service_activator')]
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
