<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceSendAndReceiveWithTwoArguments
 * @package Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendAndReceiveWithTwoArguments
{
    public function sendMail(string $personId, string $content) : string;
}