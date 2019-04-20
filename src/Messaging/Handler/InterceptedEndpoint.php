<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

/**
 * Interface InterceptedEndpoint
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InterceptedEndpoint
{
    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     * @return static
     */
    public function addAroundInterceptor(AroundInterceptorReference $aroundInterceptorReference);

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InterfaceToCall
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry) : InterfaceToCall;

    /**
     * @param object[] $endpointAnnotations
     * @return static
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations);

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): array;

    /**
     * @return string[]
     */
    public function getRequiredInterceptorNames() : iterable;

    /**
     * @param string[] $interceptorNames
     *
     * @return static
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames);
}