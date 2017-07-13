<?php

namespace Messaging;

/**
 * Interface Message
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Message
{
    /**
     * @return object|string|array
     */
    public function getPayload();
}