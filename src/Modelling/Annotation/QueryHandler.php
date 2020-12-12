<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ramsey\Uuid\Uuid;

#[\Attribute(\Attribute::TARGET_METHOD)]
class QueryHandler extends InputOutputEndpointAnnotation
{
    public function __construct(string $routingKey = "", string $endpointId = "", string $outputChannelName = "", array $requiredInterceptorNames = [])
    {
        parent::__construct($routingKey, $endpointId, $outputChannelName, $requiredInterceptorNames);
    }
}