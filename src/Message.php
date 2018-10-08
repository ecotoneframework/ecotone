<?php

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Interface Message
 * @package SimplyCodedSoftware\IntegrationMessaging
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