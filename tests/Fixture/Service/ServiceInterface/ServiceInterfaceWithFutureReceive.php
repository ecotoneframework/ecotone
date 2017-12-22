<?php

namespace Fixture\Service\ServiceInterface;

use Messaging\Future;

/**
 * Interface ServiceInterfaceWithFutureReceive
 * @package Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceWithFutureReceive
{
    /**
     * @return Future
     */
    public function someLongRunningWork() : Future;
}