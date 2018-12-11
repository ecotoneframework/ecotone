<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class ClassLevelInterceptorsExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @MessageEndpoint()
 * @ClassInterceptors({
 *    @MethodInterceptors(
 *       preCallInterceptors={@ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check")},
 *       postCallInterceptors={@ServiceActivatorInterceptor(referenceName="test", methodName="check")}
 *    )
 * })
 */
class ClassLevelInterceptorsAndMethodsExample
{
    /**
     * @ServiceActivator(endpointId="some-id", inputChannelName="test")
     * @MethodInterceptors(
     *     preCallInterceptors={@ServiceActivatorInterceptor(referenceName="validationCheck", methodName="check")}
     * )
     */
    public function intercepted() : void
    {

    }
}