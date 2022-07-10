<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface CallableService
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}