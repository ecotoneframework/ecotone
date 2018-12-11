<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class GatewayInterceptor
 * @package SimplyCodedSoftware\Messaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class GatewayInterceptor extends MethodInterceptorAnnotation
{
    /**
     * @var string
     */
    public $requestChannelName;
}