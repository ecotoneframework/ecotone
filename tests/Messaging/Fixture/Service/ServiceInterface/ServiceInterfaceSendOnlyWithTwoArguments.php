<?php

namespace Ecotone\Tests\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Ecotone\Tests\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithTwoArguments
{
    public function sendMail(int $personId, string $content) : void;
}