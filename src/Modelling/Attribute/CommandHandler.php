<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class CommandHandler extends InputOutputEndpointAnnotation
{
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     */
    public bool $dropMessageOnNotFound = false;
    public array $identifierMetadataMapping = [];

    public function __construct(string $routingKey = '', string $endpointId = '', string $outputChannelName = '', bool $dropMessageOnNotFound = false, $identifierMetadataMapping = [], array $requiredInterceptorNames = [])
    {
        parent::__construct($routingKey, $endpointId, $outputChannelName, $requiredInterceptorNames);

        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->identifierMetadataMapping = $identifierMetadataMapping;
    }

    public function isDropMessageOnNotFound(): bool
    {
        return $this->dropMessageOnNotFound;
    }

    public function getIdentifierMetadataMapping(): array
    {
        return $this->identifierMetadataMapping;
    }
}
