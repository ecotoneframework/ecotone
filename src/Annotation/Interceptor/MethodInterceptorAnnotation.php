<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;
use SimplyCodedSoftware\IntegrationMessaging\Config\OrderedMethodInterceptor;

/**
 * Class MethodInterceptorAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MethodInterceptorAnnotation
{
    /**
     * The higher is, the faster it will be called
     *
     * @var int
     */
    public $weightOrder = OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT;
}