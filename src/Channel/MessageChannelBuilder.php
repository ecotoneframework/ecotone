<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelBuilder
{
    /**
     * @return string
     */
    public function getMessageChannelName() : string;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return MessageChannel
     */
    public function build(ReferenceSearchService $referenceSearchService) : MessageChannel;

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array;
}