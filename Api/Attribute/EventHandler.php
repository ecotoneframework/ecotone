<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
/**
 * licence Apache-2.0
 */
class EventHandler extends IdentifiedAnnotation
{
    public readonly string $listenTo;
    public bool $dropMessageOnNotFound;
    public string $outputChannelName;
    public array $requiredInterceptorNames;
    public array $identifierMetadataMapping;
    public array $identifierMapping;

    public function __construct(string $listenTo = '', string $endpointId = '', string $outputChannelName = '', bool $dropMessageOnNotFound = false, array $identifierMetadataMapping = [], array $requiredInterceptorNames = [], array $identifierMapping = [])
    {
        parent::__construct($endpointId);

        $this->listenTo = $listenTo;
        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->outputChannelName = $outputChannelName;
        $this->requiredInterceptorNames = $requiredInterceptorNames;
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

    public function getOutputChannelName(): string
    {
        return $this->outputChannelName;
    }

    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }

    public function getIdentifierMetadataMapping(): array
    {
        return $this->identifierMetadataMapping;
    }

    public function getAggregateIdentifierMapping(): array
    {
        return $this->identifierMapping;
    }
}
