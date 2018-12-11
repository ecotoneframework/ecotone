<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\GatewayInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class GatewayInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor
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