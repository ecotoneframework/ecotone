<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
interface ProxyBuilder
{
    public function registerProxy(MessagingContainerBuilder $builder): Reference;
}
