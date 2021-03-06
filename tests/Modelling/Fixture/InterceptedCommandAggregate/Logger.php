<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcedAggregate;
use Ecotone\Modelling\WithAggregateVersioning;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs\ValidateExecutor;

#[EventSourcedAggregate]
#[ValidateExecutor]
class Logger
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private string $loggerId;

    private array $logs;
    private string $ownerId;

    private function __construct(string $loggerId, string $ownerId, array $logs)
    {
        $this->loggerId      = $loggerId;
        $this->logs = $logs;
        $this->ownerId = $ownerId;
    }

    #[CommandHandler("log")]
    public static function register(array $data): array
    {
        return [new EventWasLogged($data)];
    }

    #[CommandHandler("log", outputChannelName: "notify")]
    public function append(array $data) : array
    {
        return [new EventWasLogged($data)];
    }

    /**
     * @var EventWasLogged[] $events
     */
    #[AggregateFactory]
    public static function restore(array $events) : self
    {
        return new self($events[0]->getLoggerId(), $events[0]->getData()['executorId'], $events);
    }

    public function hasAccess(string $executorId) : bool
    {
        return $executorId === $this->ownerId;
    }
}