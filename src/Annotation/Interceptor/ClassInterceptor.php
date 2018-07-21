<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Interceptor;

/**
 * Class ClassInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ClassInterceptor
{
    /**
     * @var array
     */
    public $preCallInterceptors = [];
    /**
     * @var array
     */
    public $postCallInterceptors = [];
    /**
     * @var string[]
     */
    public $excludedMethods = [];
}