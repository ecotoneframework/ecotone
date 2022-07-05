<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

use Ecotone\Messaging\Future;

/**
 * Interface ServiceInterfaceWithFutureReceive
 * @package Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceWithFutureReceive
{
    /**
     * @return Future
     */
    public function someLongRunningWork() : Future;
}