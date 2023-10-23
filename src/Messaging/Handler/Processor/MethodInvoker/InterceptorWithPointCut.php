<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

/**
 * Interface Interceptor
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InterceptorWithPointCut
{
    public function getInterceptingObject(): object;

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool;

    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations, InterfaceToCallRegistry $interfaceToCallRegistry): bool;

    /**
     * @param InterfaceToCall $interceptedInterface
     * @param AttributeDefinition[] $endpointAnnotations
     * @return static
     */
    public function addInterceptedInterfaceToCall(InterfaceToCall $interceptedInterface, array $endpointAnnotations);
}
