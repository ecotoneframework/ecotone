<?php

namespace Tests\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface CallableService
 * @package Tests\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled() : bool;
}