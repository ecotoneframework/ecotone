<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class ClassInterceptor
 * @package SimplyCodedSoftware\Messaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ClassInterceptors
{
    /**
     * @var array
     */
    public $classMethodsInterceptors = [];
}