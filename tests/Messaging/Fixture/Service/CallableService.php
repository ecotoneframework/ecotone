<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;

/**
 * Interface CallableService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}