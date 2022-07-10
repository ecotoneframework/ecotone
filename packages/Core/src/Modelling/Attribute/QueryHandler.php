<?php

namespace Ecotone\Modelling\Attribute;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use Ramsey\Uuid\Uuid;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
class QueryHandler extends InputOutputEndpointAnnotation
{
    public function __construct(string $routingKey = "", string $endpointId = "", string $outputChannelName = "", array $requiredInterceptorNames = [])
    {
        parent::__construct($routingKey, $endpointId, $outputChannelName, $requiredInterceptorNames);
    }
}