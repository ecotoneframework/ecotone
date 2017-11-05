<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithTwoArguments
{
    public function sendMail(int $personId, string $content) : void;
}