<?php

namespace Test\Ecotone\EventSourcing\Fixture\Snapshots;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Test\Ecotone\EventSourcing\Fixture\Basket\Basket;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;

#[MediaTypeConverter]
final class BasketMediaTypeConverter implements Converter
{
    /**
     * @param Basket $source
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if ($targetMediaType->isCompatibleWith(MediaType::createApplicationJson())) {
            return \json_encode($source->toArray());
        }

        return Basket::fromArray(\json_decode($source, true));
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->getTypeHint() === Basket::class || $targetType->getTypeHint() === Basket::class;
    }
}