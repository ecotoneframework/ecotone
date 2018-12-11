<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

/**
 * Class ServiceWithReturnValue
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithReturnValue implements CallableService
{
    /**
     * @var bool
     */
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        $this->wasCalled = true;
        return 'johny';
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}