<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface EnrichReferenceService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
interface EnrichGateway
{
    /**
     * @param Message $message
     *
     * @return Message
     */
    public function execute(Message $message) : Message;
}