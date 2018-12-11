<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithTwoArguments
{
    public function sendMail(int $personId, string $content) : void;
}