<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Interceptor;

/**
 * Class Annotation
 * @package Ecotone\Messaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MethodInterceptor
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}