<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
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