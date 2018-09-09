<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Class InterceptedEndpointAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptor
{
    /**
     * List of method level pre interceptors
     *
     * @var array
     */
    public $preCallInterceptors = [];
    /**
     * List of method level post interceptors
     *
     * @var array
     */
    public $postCallInterceptors = [];
}