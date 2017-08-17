<?php

namespace Fixture\Service;

/**
 * Class ServiceWithoutReturnValue
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutReturnValue implements CallableService
{
    /**
     * @var bool
     */
    private $wasCalled = false;

    public function setName(string $name) : void
    {
        $this->wasCalled = true;
        return;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}