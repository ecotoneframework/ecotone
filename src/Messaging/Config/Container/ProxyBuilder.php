<?php

namespace Ecotone\Messaging\Config\Container;

interface ProxyBuilder
{
    public function registerProxy(MessagingContainerBuilder $builder): Reference;
}
