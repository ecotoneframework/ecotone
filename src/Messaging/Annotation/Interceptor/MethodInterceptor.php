<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class Annotation
 * @package SimplyCodedSoftware\Messaging\Annotation\Interceptor
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