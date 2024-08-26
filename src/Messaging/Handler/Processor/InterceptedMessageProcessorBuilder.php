<?php

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

/**
 * @licence Apache-2.0
 */
interface InterceptedMessageProcessorBuilder extends CompilableBuilder
{
    public function getInterceptedInterface(): InterfaceToCallReference;
    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition|Reference;
}
