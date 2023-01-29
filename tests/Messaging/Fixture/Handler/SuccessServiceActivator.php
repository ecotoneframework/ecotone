<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;

final class SuccessServiceActivator
{
    #[Asynchronous('async_channel')]
    #[ServiceActivator('handle_channel')]
    public function handle(Message $message): void
    {
    }

    public function __toString()
    {
        return self::class;
    }
}
