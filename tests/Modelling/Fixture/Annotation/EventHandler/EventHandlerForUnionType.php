<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class EventHandlerForUnionType
{
    /** @var array<object> */
    private array $handledEvents = [];

    #[EventHandler]
    public function doSomething(stdClass|OrderWasPlaced $event): void
    {
        $this->handledEvents[] = $event;
    }

    /**
     * @return array<object>
     */
    #[QueryHandler('getHandledEvents')]
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }
}
