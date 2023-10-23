<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;

/**
 * Interface InterceptedEndpoint
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InterceptedEndpoint
{
    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     * @return static
     */
    public function addAroundInterceptor(AroundInterceptorBuilder $aroundInterceptorReference);

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InterfaceToCall
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall;

    /**
     * @param AttributeDefinition[] $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations);

    /**
     * @return AttributeDefinition[]
     */
    public function getEndpointAnnotations(): array;

    /**
     * @return string[]
     */
    public function getRequiredInterceptorNames(): iterable;

    /**
     * @param string[] $interceptorNames
     *
     * @return static
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames);
}
