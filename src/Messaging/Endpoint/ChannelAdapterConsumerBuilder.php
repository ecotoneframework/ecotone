<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface ConsumerBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
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