<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface ConsumerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelAdapterConsumerBuilder
{
    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @param ChannelResolver        $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return ConsumerLifecycle
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : ConsumerLifecycle;
}