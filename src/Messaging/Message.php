<?php

namespace Ecotone\Messaging;

/**
 * Interface Message
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Message
{
    /**
     * @return MessageHeaders
     */
    public function getHeaders() : MessageHeaders;

    /**
     * @return mixed
     */
    public function getPayload();
}