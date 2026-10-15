<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Modelling\WithEvents;

#[Saga]
/**
 * licence Apache-2.0
 */
final class SagaWithMultipleEventHandlers
{
    use WithEvents;

    public function __construct(
        #[Identifier]
        private string $orderId,
        public int $actionOneCalled = 0,
        public int $actionTwoCalled = 0
    ) {
        $this->recordThat(new SagaCreatedEvent($orderId));
    }

    #[EventHandler]
    public static function create(RandomEvent $event): self
    {
        return new self($event->orderId);
    }

    #[EventHandler]
    public function actionOne(SagaCreatedEvent $event): void
    {
        $this->actionOneCalled++;
    }

    #[EventHandler]
    public function actionTwo(SagaCreatedEvent $event): void
    {
        $this->actionTwoCalled++;
    }
}
