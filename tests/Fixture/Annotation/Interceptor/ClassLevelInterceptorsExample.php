<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ClassLevelInterceptorsExample
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @ClassInterceptors({
 *    @MethodInterceptors(
 *       preCallInterceptors={@ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check")},
 *       postCallInterceptors={@ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check")},
 *       excludedMethods={"notIntercepted"}
 *    )
 * })
 */
class ClassLevelInterceptorsExample
{
    /**
     * @ServiceActivator(inputChannelName="test")
     */
    public function intercepted() : void
    {

    }

    /**
     * @ServiceActivator(inputChannelName="test")
     */
    public function notIntercepted() : void
    {

    }
}