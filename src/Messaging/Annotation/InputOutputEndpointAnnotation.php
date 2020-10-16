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
    public string $outputChannelName = '';
    /**
     * Required interceptor reference names
     */
    public array $requiredInterceptorNames = [];
}