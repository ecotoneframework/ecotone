<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Saga;
use Ecotone\Modelling\Attribute\SagaIdentifier;
use Ecotone\Modelling\WithEvents;

#[Saga]
final class SagaWithMultipleEventHandlers
{
    use WithEvents;

    public function __construct(
        #[SagaIdentifier] private string $orderId,
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
