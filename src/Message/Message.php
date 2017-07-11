<?php

namespace Messaging\Message;

/**
 * Interface Message
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Message
{
    /**
     * @return object|string|array
     */
    public function getPayload();
}