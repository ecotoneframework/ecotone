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

    public function withNullReturnValue(string $name) : ?string
    {
        return null;
    }

    public function withArrayReturnValue(string $name) : array
    {
        return [$name];
    }

    public function withArrayTypeHintAndArrayReturnValue(array $values) : array
    {
        return $values;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}