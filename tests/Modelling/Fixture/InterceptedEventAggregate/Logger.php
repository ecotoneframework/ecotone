<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\EventWasLogged;
use Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\ValidateExecutor;

#[EventSourcingAggregate]
#[ValidateExecutor]
/**
 * licence Apache-2.0
 */
class Logger
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $loggerId;
    private array $logs;
    private string $ownerId;

    #[EventHandler('order.was_created')]
    public static function register(array $data): array
    {
        return [new EventWasLogged($data)];
    }

    #[EventHandler('order.was_created', outputChannelName: 'notify')]
    public function append(array $data): array
    {
        return [new EventWasLogged($data)];
    }

    #[Apply]
    public function onEventWasLogged(EventWasLogged $event): void
    {
        $this->loggerId      = $event->getLoggerId();
        $this->ownerId = $event->getData()['executorId'];
        $this->logs[] = $event;
    }

    public function hasAccess(string $executorId): bool
    {
        return $executorId === $this->ownerId;
    }
}
