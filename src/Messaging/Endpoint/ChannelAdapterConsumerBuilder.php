<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;

/**
 * Interface ConsumerBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ChannelAdapterConsumerBuilder extends ConsumerLifecycleBuilder
{
    /**
     * @return string
     */
    public function getEndpointId(): string;

    public function registerConsumer(MessagingContainerBuilder $builder): void;
}
