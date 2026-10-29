<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\InputOutputEndpointAnnotation;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
/**
 * licence Apache-2.0
 */
class CommandHandler extends InputOutputEndpointAnnotation
{
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     */
    public bool $dropMessageOnNotFound = false;
    public array $identifierMetadataMapping = [];
    public array $identifierMapping = [];

    public function __construct(string $routingKey = '', string $endpointId = '', string $outputChannelName = '', bool $dropMessageOnNotFound = false, $identifierMetadataMapping = [], array $requiredInterceptorNames = [], array $identifierMapping = [])
    {
        parent::__construct($routingKey, $endpointId, $outputChannelName, $requiredInterceptorNames);

        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->identifierMetadataMapping = $identifierMetadataMapping;
        $this->identifierMapping = $identifierMapping;

        if ($identifierMetadataMapping && $identifierMapping) {
            throw new InvalidArgumentException("You can't define both `identifierMetadataMapping` and `identifierMapping`");
        }
    }

    public function isDropMessageOnNotFound(): bool
    {
        return $this->dropMessageOnNotFound;
    }

    public function getIdentifierMetadataMapping(): array
    {
        return $this->identifierMetadataMapping;
    }

    public function getIdentifierMapping(): array
    {
        return $this->identifierMapping;
    }
}
