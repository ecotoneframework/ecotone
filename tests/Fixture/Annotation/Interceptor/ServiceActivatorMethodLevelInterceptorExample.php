<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorMethodLevelInterceptor
 * @package Fixture\Annotation\Interceptor
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