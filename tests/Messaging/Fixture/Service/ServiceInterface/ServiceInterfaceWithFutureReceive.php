<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

use SimplyCodedSoftware\Messaging\Future;

/**
 * Interface ServiceInterfaceWithFutureReceive
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceWithFutureReceive
{
    /**
     * @return Future
     */
    public function someLongRunningWork() : Future;
}