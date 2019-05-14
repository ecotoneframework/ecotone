<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;

/**
 * Class InputOutputEndpointAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
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