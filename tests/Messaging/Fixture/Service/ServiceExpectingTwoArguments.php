<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

/**
 * Class ServiceExpectingTwoArguments
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingTwoArguments
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    public function withReturnValue(string $name, string $surname) : string
    {
        $this->wasCalled = true;
        return $name . $surname;
    }

    public function withoutReturnValue(string $name, string $surname) : void
    {
        $this->wasCalled = true;
    }

    public function payloadAndHeaders($payload, array $headers)
    {
        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}