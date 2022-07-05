<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceReceiveOnly
{
    public function sendMail() : string;
}