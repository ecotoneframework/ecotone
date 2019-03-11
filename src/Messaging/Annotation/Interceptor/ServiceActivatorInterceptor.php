<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Interface ServiceActivatorInterceptor
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ServiceActivatorInterceptor
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}