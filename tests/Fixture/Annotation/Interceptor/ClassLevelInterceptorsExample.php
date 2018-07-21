<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ClassInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ClassLevelInterceptorsExample
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @ClassInterceptor(
 *     preCallInterceptors={
 *        @ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check"),
 *        @EnricherInterceptor(requestMessageChannel="addCurrentUserId", converters={
 *                
 *        })
 *     },
 *     excludedMethods={"notIntercepted"}
 * )
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