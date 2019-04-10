<?php


namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;

/**
 * Interface Interceptor
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InterceptorWithPointCut
{
    /**
     * @return object
     */
    public function getInterceptingObject();

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name) : bool;

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param iterable $endpointAnnotations
     * @return bool
     */
    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations): bool;
}