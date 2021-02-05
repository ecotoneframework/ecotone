<?php


namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;


class JobWasFinished
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function recordWith(string $id) : self
    {
        return new self($id);
    }

    public function getId(): string
    {
        return $this->id;
    }
}