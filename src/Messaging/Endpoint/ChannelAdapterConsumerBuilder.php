<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;

/**
 * Interface ConsumerBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelAdapterConsumerBuilder extends ConsumerLifecycleBuilder, InterceptedEndpoint
{
    /**
     * @return string
     */
    public function getEndpointId(): string;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addBeforeInterceptor(MethodInterceptor $methodInterceptor): self;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return $this
     */
    public function addAfterInterceptor(MethodInterceptor $methodInterceptor): self;

    public function registerConsumer(MessagingContainerBuilder $builder): void;
}
