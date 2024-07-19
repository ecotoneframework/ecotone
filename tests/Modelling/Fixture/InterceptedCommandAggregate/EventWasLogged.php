<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

/**
 * licence Apache-2.0
 */
class EventWasLogged
{
    private string $loggerId;

    private array $data;

    public function __construct(array $data)
    {
        $this->loggerId = $data['loggerId'];
        unset($data['loggerId']);
        $this->data     = $data;
    }


    public function getLoggerId(): string
    {
        return $this->loggerId;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
