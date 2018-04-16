<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface Gateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayBuilder
{
    /**
     * Name to be registered under
     *
     * @return string
     */
    public function getReferenceName() : string;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @return string
     */
    public function getRequestChannelName() : string;

    /**
     * @return string
     */
    public function getInterfaceName() : string;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return object
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver);
}