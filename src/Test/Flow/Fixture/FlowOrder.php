<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;

/**
 * licence Apache-2.0
 */
#[EventSourcingAggregate]
#[AggregateType('flow_order')]
final class FlowOrder
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $orderId;

    #[CommandHandler('flow_order.place', endpointId: 'flow_order.placing')]
    public static function place(array $command): array
    {
        return [new FlowOrderPlaced($command['orderId'])];
    }

    #[Apply]
    public function applyPlaced(FlowOrderPlaced $event): void
    {
        $this->orderId = $event->orderId;
    }
}
