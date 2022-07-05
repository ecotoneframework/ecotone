<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceReceiveOnlyStdClass
{
    public function sendMail() : \stdClass;
}