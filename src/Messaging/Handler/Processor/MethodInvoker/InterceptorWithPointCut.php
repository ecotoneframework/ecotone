<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface Interceptor
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface InterceptorWithPointCut
{
    /**
     * @param array<AttributeDefinition> $endpointAnnotations
     */
    public function compileForInterceptedInterface(
        MessagingContainerBuilder $builder,
        InterfaceToCallReference  $interceptedInterfaceToCallReference,
        array                     $endpointAnnotations = []
    ): Definition|Reference;

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool;

    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations): bool;
}
