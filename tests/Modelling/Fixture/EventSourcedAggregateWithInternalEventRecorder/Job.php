<?php


namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventSourcedAggregate;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Ecotone\Modelling\WithVersioning;

#[EventSourcedAggregate(true)]
class Job
{
    use WithAggregateEvents;
    use WithVersioning;

    #[AggregateIdentifier]
    private string $id;
    private bool $isInProgress;

    private function __construct() {}

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

    #[AggregateFactory]
    public static function restore(array $events) : self
    {
        $job = new static();

        foreach ($events as $event) {
            $job = match (get_class($event)) {
                JobWasStarted::class => static::whenJobWasStarted($event, $job),
                JobWasFinished::class => static::whenJobWasFinished($event, $job)
            };
        }

        return $job;
    }

    private static function whenJobWasStarted(JobWasStarted $event, self $job) : static
    {
        $job->id = $event->getId();
        $job->isInProgress = true;

        return $job;
    }

    private static function whenJobWasFinished(JobWasFinished $event, self $job) : static
    {
        $job->isInProgress = false;

        return $job;
    }
}