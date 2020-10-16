<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * Class ServiceWithoutReturnValue
 * @package Test\Ecotone\Messaging\Fixture\Service
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
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     */
    public function callWithInterfaceToCall(InterfaceToCall $interfaceToCall) : void
    {

    }

    public function callWithAnnotation(Transactional $transactional) : void
    {

    }

    public function callWithNullableAnnotation(?Transactional $transactional) : void
    {

    }
}