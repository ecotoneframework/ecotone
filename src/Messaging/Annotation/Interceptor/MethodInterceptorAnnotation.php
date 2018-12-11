<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;
use SimplyCodedSoftware\Messaging\Config\OrderedMethodInterceptor;

/**
 * Class MethodInterceptorAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\Interceptor
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