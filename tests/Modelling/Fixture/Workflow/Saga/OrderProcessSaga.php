<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;
use Ecotone\Modelling\WithEvents;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command\TakePayment;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event\OrderProcessSagaStarted;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event\OrderWasPlaced;

#[Saga]
/**
 * licence Apache-2.0
 */
final class OrderProcessSaga
{
    use WithEvents;

    public function __construct(
        #[Identifier] private string $orderId,
        private bool $shouldTakePayment
    ) {
        $this->recordThat(new OrderProcessSagaStarted($orderId));
    }

    #[EventHandler]
    public static function whenOrderWasPlaced(
        OrderWasPlaced $event,
        #[Header('shouldTakePayment')] bool $shouldTakePayment = true
    ): self {
        return new self($event->orderId, $shouldTakePayment);
    }

    #[EventHandler(outputChannelName: 'takePayment')]
    public function triggerPayment(OrderProcessSagaStarted $event): ?TakePayment
    {
        if (! $this->shouldTakePayment) {
            return null;
        }

        return new TakePayment($event->orderId);
    }
}
