<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceWithReplyAndNoArguments
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceReceiveOnly
{
    /**
     * @param int $number
     * @return string
     */
    public function receiveMessage(int $number) : string;
}