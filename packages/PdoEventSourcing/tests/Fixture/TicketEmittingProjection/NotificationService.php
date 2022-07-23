<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection;

use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\Prooph\LazyProophProjectionManager;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Event;

use function end;

final class NotificationService
{
    private array $publishedEvents = [];

    #[QueryHandler('get.notifications')]
    public function getNotifications(#[Reference] EventStore $eventStore): ?string
    {
        $projectionStreamName = LazyProophProjectionManager::getProjectionStreamName(InProgressTicketList::NAME);
        if (! $eventStore->hasStream($projectionStreamName)) {
            return null;
        }

        /** @var Event[] $events */
        $events = $eventStore->load($projectionStreamName);

        return end($events)->getPayload()->ticketId;
    }

    #[EventHandler]
    public function subscribeToProjectionEvent(TicketListUpdated $event): void
    {
        $this->publishedEvents[] = $event;
    }

    #[QueryHandler('get.published_events')]
    public function lastPublishedEvent(): array
    {
        return $this->publishedEvents;
    }
}
