<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\AttributeDefinition;

/**
 * Interface InterceptedEndpoint
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface InterceptedEndpoint
{
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
