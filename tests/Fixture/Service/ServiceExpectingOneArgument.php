<?php

namespace Fixture\Service;

/**
 * Class ServiceExpectingOneArgument
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingOneArgument
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    public function withReturnValue(string $name) : string
    {
        $this->wasCalled = true;
        return $name;
    }

    public function withoutReturnValue(string $name) : void
    {
        $this->wasCalled = true;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}