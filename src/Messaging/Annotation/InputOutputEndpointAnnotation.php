<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

abstract class InputOutputEndpointAnnotation extends EndpointAnnotation
{
    public string $outputChannelName = '';
    /**
     * Required interceptor reference names
     */
    public array $requiredInterceptorNames = [];
}