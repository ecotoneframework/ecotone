<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceSendAndReceiveWithTwoArguments
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendAndReceiveWithTwoArguments
{
    public function sendMail(string $personId, string $content) : string;
}