<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\SameChannelAndRouting;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * @TODO Ecotone 2.0 routing keys are not message channels, so asynchronous channel can be equal to routing key
 */
final class SomeTestCommandHandler
{
    #[Asynchronous('input')]
    #[CommandHandler(routingKey: 'input', endpointId: 'test')]
    public function test(): void
    {
    }
}
