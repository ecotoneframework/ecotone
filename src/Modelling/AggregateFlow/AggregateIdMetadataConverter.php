<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * licence Apache-2.0
 */
final class AggregateIdMetadataConverter implements CompilableBuilder, Converter
{
    public function from(AggregateIdMetadata $aggregateIdMetadata): string
    {
        return json_encode($aggregateIdMetadata->getIdentifiers(), JSON_THROW_ON_ERROR);
    }

    public function to(string $aggregateIdMetadata): AggregateIdMetadata
    {
        return new AggregateIdMetadata(json_decode($aggregateIdMetadata, true, 512, JSON_THROW_ON_ERROR));
    }

    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if ($targetType->isCompatibleWith(TypeDescriptor::create(AggregateIdMetadata::class))) {
            return $this->to($source);
        }

        return $this->from($source);
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $sourceType->isCompatibleWith(TypeDescriptor::create(AggregateIdMetadata::class))
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $targetType->isCompatibleWith(TypeDescriptor::createStringType());
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(self::class);
    }
}
