<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
final class DelayedClosureService
{
    #[Delayed(expression: static function (#[Payload] DelayCommand $command): int {
        return $command->delay;
    })]
    #[Asynchronous('async')]
    #[CommandHandler('notification.delayed', endpointId: 'notificationDelayedEndpoint')]
    public function handle(DelayCommand $command): void
    {
    }
}
