<?php


namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;


class StartJob
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}