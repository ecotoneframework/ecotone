<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\QueryHandler;

final class SuccessServiceActivator
{
    private int $calls = 0;

    #[Asynchronous('async_channel')]
    #[ServiceActivator('handle_channel')]
    public function handle(Message $message): void
    {
        $this->calls++;
    }

    #[QueryHandler('get_number_of_calls')]
    public function getNumberOfCalls(): int
    {
        return $this->calls;
    }

    public function __toString()
    {
        return self::class;
    }
}
