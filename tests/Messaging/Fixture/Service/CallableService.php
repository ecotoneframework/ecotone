<?php

namespace Ecotone\Tests\Messaging\Fixture\Service;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface CallableService
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}