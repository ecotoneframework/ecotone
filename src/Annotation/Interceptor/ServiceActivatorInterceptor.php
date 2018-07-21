<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Interface ServiceActivatorInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ServiceActivatorInterceptor
{
    /**
     * @var string
     * @Required()
     */
    public $referenceName;
    /**
     * @var string
     * @Required()
     */
    public $methodName;
    /**
     * @var array
     */
    public $parameterConverters = [];
}