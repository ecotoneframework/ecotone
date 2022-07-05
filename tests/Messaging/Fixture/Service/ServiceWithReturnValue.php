<?php

namespace Tests\Ecotone\Messaging\Fixture\Service;

/**
 * Class ServiceWithReturnValue
 * @package Tests\Ecotone\Messaging\Fixture\Service
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