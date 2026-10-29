<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;

#[Saga]
/**
 * licence Apache-2.0
 */
final class SagaWithMultipleEventHandlersAndFactoryMethod
{
    public function __construct(
        #[Identifier]
        private string $orderId,
        private int $actionOneCalled = 0,
        private int $actionTwoCalled = 0
    ) {
    }

    #[EventHandler]
    public function actionOne(RandomEvent $event): void
    {
        $this->actionOneCalled++;
    }

    #[EventHandler]
    public function actionTwo(RandomEvent $event): void
    {
        $this->actionTwoCalled++;
    }

    #[EventHandler]
    public static function create(RandomEvent $event): self
    {
        return new self($event->orderId);
    }
}
