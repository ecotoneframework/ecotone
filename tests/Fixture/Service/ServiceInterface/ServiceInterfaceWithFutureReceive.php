<?php

namespace Fixture\Service\ServiceInterface;

use SimplyCodedSoftware\IntegrationMessaging\Future;

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