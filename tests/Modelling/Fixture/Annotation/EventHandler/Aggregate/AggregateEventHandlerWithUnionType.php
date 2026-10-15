<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use stdClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateEventHandlerWithUnionType
{
    #[Identifier]
    private string $id;

    /** @var array<object> */
    private array $handledEvents = [];

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    #[CommandHandler('aggregate.create')]
    public static function create(string $id): self
    {
        return new self($id);
    }

    #[EventHandler]
    public function onEvent(stdClass|OrderWasPlaced $event): void
    {
        $this->handledEvents[] = $event;
    }

    /**
     * @return array<object>
     */
    #[QueryHandler('aggregate.getHandledEvents')]
    public function getHandledEvents(): array
    {
        return $this->handledEvents;
    }
}
