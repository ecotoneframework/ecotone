<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

/**
 * Class ServiceWithoutReturnValue
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithoutReturnValue implements CallableService
{
    /**
     * @var bool
     */
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

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