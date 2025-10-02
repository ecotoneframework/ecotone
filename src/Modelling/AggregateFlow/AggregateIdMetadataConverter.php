<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;

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

    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if ($targetType->isCompatibleWith(Type::object(AggregateIdMetadata::class))) {
            return $this->to($source);
        }

        return $this->from($source);
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $sourceType->isCompatibleWith(Type::object(AggregateIdMetadata::class))
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $targetType->isCompatibleWith(Type::string());
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(self::class);
    }
}
