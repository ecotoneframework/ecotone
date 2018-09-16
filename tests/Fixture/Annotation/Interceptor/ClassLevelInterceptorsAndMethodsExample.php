<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ClassLevelInterceptorsExample
 * @package Fixture\Annotation\Interceptor
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