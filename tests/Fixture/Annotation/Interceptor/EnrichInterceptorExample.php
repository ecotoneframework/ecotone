<?php
declare(strict_types=1);

namespace Fixture\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\EnricherInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor\MethodInterceptors;

/**
 * Class EnrichInterceptorExample
 * @package Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichInterceptorExample
{
    /**
     * @MethodInterceptors(
     *     preCallInterceptors={
     *          @EnricherInterceptor(
     *              requestMessageChannel="requestChannel",
     *              requestPayloadExpression="payload['name']",
     *              requestHeaders={
     *                  "token"="1234"
     *              },
     *          )
     *     }
     * )
     */
    public function execute() : void
    {

    }
}