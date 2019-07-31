<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

/**
 * Class ServiceExpectingTwoArguments
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingThreeArguments
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    public function withReturnValue(string $name, string $surname, int $age) : string
    {
        $this->wasCalled = true;
        return $name . $surname . $age;
    }

    public function withoutReturnValue(string $name, string $surname, int $age) : void
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