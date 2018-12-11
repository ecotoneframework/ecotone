<?php

namespace SimplyCodedSoftware\Messaging;

/**
 * Interface Message
 * @package SimplyCodedSoftware\Messaging
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