<?php

namespace Tests\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Tests\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceReceiveOnlyStdClass
{
    public function sendMail() : \stdClass;
}