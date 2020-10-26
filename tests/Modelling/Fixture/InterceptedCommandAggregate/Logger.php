<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs\ValidateExecutor;

#[Aggregate]
#[ValidateExecutor]
class Logger
{
    /**
     * @AggregateIdentifier()
     */
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
     * @AggregateFactory()
     * @var EventWasLogged[] $events
     */
    public static function restore(array $events) : self
    {
        return new self($events[0]->getLoggerId(), $events[0]->getData()['executorId'], $events);
    }

    public function hasAccess(string $executorId) : bool
    {
        return $executorId === $this->ownerId;
    }
}