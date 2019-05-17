<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;

/**
 * Interface MethodInvocation
 * @package SimplyCodedSoftware\Messaging\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodInvocation
{
    /**
     * Proceed with invocation. Returns value of invoked method
     *
     * @return mixed
     */
    public function proceed();

    /**
     * @return object
     */
    public function getInterceptedInstance();

    /**
     * @return string
     */
    public function getInterceptedClassName() : string;

    /**
     * @return string
     */
    public function getInterceptedMethodName() : string;

    /**
     * @return InterfaceToCall
     */
    public function getInterceptedInterface() : InterfaceToCall;

    /**
     * @return object[]
     */
    public function getEndpointAnnotations() : iterable;

    /**
     * @return array
     */
    public function getArguments() : array;

    /**
     * @param string $parameterName
     * @param mixed $value
     * @return void
     */
    public function replaceArgument(string $parameterName, $value) : void;
}