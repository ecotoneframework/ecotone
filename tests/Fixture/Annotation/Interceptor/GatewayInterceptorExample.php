<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\GatewayInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class GatewayInterceptorExample
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class GatewayInterceptorExample
{
    /**
     * @param string $some
     *
     * @ServiceActivator(endpointId="some-id")
     * @MethodInterceptors(
     *     preCallInterceptors={
     *          @GatewayInterceptor(requestChannelName="requestChannel")
     *     }
     * )
     */
    public function doStuff(string $some) : void
    {

    }
}