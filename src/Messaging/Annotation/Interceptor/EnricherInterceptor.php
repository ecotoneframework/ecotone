<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Interceptor;

/**
 * Class EnricherInterceptor
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnricherInterceptor extends MethodInterceptorAnnotation
{
    /**
     * @var string
     */
    public $requestMessageChannel = "";
    /**
     * @var string
     */
    public $requestPayloadExpression = "";
    /**
     * @var array
     */
    public $requestHeaders = [];
    /**
     * @var array
     */
    public $editors;
}