<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceWithRequestAndReply
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendAndReceive
{
    public function getById(int $id) : string;
}