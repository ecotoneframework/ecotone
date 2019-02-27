<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

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
     * @param string $parameterName
     * @param mixed $value
     * @return void
     */
    public function replaceArgument(string $parameterName, $value) : void;
}