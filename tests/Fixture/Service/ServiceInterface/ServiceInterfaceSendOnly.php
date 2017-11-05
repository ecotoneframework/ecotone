<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnly
{
    public function sendMail(string $content) : void;
}