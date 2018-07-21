<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;

/**
 * Class ServiceActivatorMethodLevelInterceptor
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorMethodLevelInterceptorExample
{
    /**
     * @param string $message
     * @ServiceActivator(
     *      preCallInterceptors={
     *          @ServiceActivatorInterceptor(referenceName="authorizationService", methodName="check")
     *      }
     * )
     */
    public function send(string $message) : void
    {
        //doing something
    }
}