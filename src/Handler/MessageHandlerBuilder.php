<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Interface MessageHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilder
{
    /**
     * @return MessageHandler
     */
    public function build() : MessageHandler;

    /**
     * @return string
     */
    public function getConsumerName() : string;

    /**
     * @return string
     */
    public function getInputMessageChannelName() : string;

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array;

    /**
     * @param ChannelResolver $channelResolver
     * @return MessageHandlerBuilder
     */
    public function setChannelResolver(ChannelResolver $channelResolver) : MessageHandlerBuilder;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return MessageHandlerBuilder
     */
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService) : MessageHandlerBuilder;
}