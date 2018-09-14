<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Class ClassInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor
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