<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorMethodLevelInterceptor
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ServiceActivatorMethodLevelInterceptorExample
{
    /**
     * @param string $message
     * @ServiceActivator(endpointId="some-id", inputChannelName="inputChannel")
     * @MethodInterceptors(
     *     preCallInterceptors={@ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check", weightOrder=2)},
     *     postCallInterceptors={@ServiceActivatorInterceptor(referenceName="test", methodName="check", weightOrder=1)}
     * )
     */
    public function send(string $message) : void
    {
        //doing something
    }
}