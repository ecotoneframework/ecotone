<?php


namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate(true)]
class Job
{
    use WithAggregateEvents;
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private string $id;
    private bool $isInProgress;

    #[CommandHandler]
    public static function start(StartJob $command) : self
    {
        $self = new static();
        $self->recordThat(JobWasStarted::recordWith($command->getId()));

        return $self;
    }

    #[CommandHandler]
    public function finish(FinishJob $command) : void
    {
        $this->recordThat(JobWasFinished::recordWith($command->getId()));
    }

    #[QueryHandler("job.isInProgress")]
    public function isInProgress() : bool
    {
        return $this->isInProgress;
    }

    #[EventSourcingHandler]
    public function whenJobWasStarted(JobWasStarted $event) : void
    {
        $this->id = $event->getId();
        $this->isInProgress = true;
    }

    #[EventSourcingHandler]
    public function whenJobWasFinished(JobWasFinished $event) : void
    {
        $this->isInProgress = false;
    }
}