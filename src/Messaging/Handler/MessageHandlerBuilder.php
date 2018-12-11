<?php
declare(strict_types=1);

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
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @return MessageHandler
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler;

    /**
     * @param string $inputChannelName
     *
     * @return static
     */
    public function withInputChannelName(string $inputChannelName);

    /**
     * @return string|null
     */
    public function getEndpointId() : ?string;

    /**
     * @param string $endpointId
     *
     * @return static
     */
    public function withEndpointId(string $endpointId);

    /**
     * @return string
     */
    public function getInputMessageChannelName() : string;

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array;
}