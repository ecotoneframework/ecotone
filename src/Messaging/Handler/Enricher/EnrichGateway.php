<?php

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface EnrichReferenceService
 * @package SimplyCodedSoftware\Messaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
interface EnrichGateway
{
    /**
     * @param Message|null $message
     *
     * @return Message
     */
    public function execute(Message $message) : ?Message;
}