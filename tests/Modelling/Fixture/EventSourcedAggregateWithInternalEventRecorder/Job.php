<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;

#[EventSourcingAggregate(true)]
/**
 * licence Apache-2.0
 */
class Job
{
    use WithEvents;
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

    #[Apply]
    public function whenJobWasStarted(JobWasStarted $event): void
    {
        $this->id = $event->getId();
        $this->isInProgress = true;
    }

    #[Apply]
    public function whenJobWasFinished(JobWasFinished $event): void
    {
        $this->isInProgress = false;
    }
}
