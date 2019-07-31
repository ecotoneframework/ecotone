<?php

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Message;

/**
 * Interface EnrichReferenceService
 * @package Ecotone\Messaging\Handler\Transformer
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