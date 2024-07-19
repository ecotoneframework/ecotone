<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\PublishAndThrowException;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\EventBus;
use InvalidArgumentException;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

/**
 * licence Apache-2.0
 */
final class FailureOrderService
{
    /**
     * @var int[]
     */
    private $notifiedOrders = [];

    #[CommandHandler]
    public function handle(PlaceOrder $command, EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced($command->getOrderId()));

        throw new InvalidArgumentException('Some exception');
    }

    #[Asynchronous('orders')]
    #[EventHandler(endpointId: 'notifyOrderWasPlaced')]
    public function notify(OrderWasPlaced $orderWasPlaced): void
    {
        $this->notifiedOrders[] = $orderWasPlaced->getOrderId();
    }

    #[QueryHandler('order.getNotifiedOrders')]
    public function getNotifiedOrders(): array
    {
        return $this->notifiedOrders;
    }
}
