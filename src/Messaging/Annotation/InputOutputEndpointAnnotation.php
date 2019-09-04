<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

/**
 * Class InputOutputEndpointAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InputOutputEndpointAnnotation extends EndpointAnnotation
{
    /**
     * @var string
     */
    public $outputChannelName = '';
    /**
     * Required interceptor reference names
     *
     * @var array
     */
    public $requiredInterceptorNames = [];
}