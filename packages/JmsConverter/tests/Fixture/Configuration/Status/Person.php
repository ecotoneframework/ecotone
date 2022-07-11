<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\Status;

class Person
{
    private Status $status;

    /**
     * Person constructor.
     * @param Status $status
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }
}
