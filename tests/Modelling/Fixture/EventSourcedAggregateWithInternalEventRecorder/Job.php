<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate(true)]
/**
 * licence Apache-2.0
 */
class Job
{
    use WithAggregateEvents;
    use WithAggregateVersioning;

    #[Identifier]
    private string $id;
    private bool $isInProgress;

    #[CommandHandler]
    public static function start(StartJob $command): self
    {
        $self = new static();
        $self->recordThat(JobWasStarted::recordWith($command->getId()));

        return $self;
    }

    #[CommandHandler]
    public function finish(FinishJob $command): void
    {
        $this->recordThat(JobWasFinished::recordWith($command->getId()));
    }

    #[CommandHandler('job.finish_and_start')]
    public function finishAndStartNewJob(FinishJob $command, #[Header('newJobId')] string $newJobId): Job
    {
        $this->recordThat(JobWasFinished::recordWith($command->getId()));

        return self::start(new StartJob($newJobId));
    }

    #[QueryHandler('job.isInProgress')]
    public function isInProgress(): bool
    {
        return $this->isInProgress;
    }

    #[EventSourcingHandler]
    public function whenJobWasStarted(JobWasStarted $event): void
    {
        $this->id = $event->getId();
        $this->isInProgress = true;
    }

    #[EventSourcingHandler]
    public function whenJobWasFinished(JobWasFinished $event): void
    {
        $this->isInProgress = false;
    }
}
