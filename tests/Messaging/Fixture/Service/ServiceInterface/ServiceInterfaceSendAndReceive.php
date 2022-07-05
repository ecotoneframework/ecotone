<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceWithRequestAndReply
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendAndReceive
{
    public function getById(int $id) : ?string;
}