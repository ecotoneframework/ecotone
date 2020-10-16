<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class CollectionConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CollectionConverter implements Converter
{
    private \Ecotone\Messaging\Conversion\Converter $converterForSingleType;

    /**
     * CollectionConverter constructor.
     * @param Converter $converterForSingleType
     */
    private function __construct(Converter $converterForSingleType)
    {
        $this->converterForSingleType = $converterForSingleType;
    }

    /**
     * @param Converter $converterForSingleType
     * @return CollectionConverter
     */
    public static function createForConverter(Converter $converterForSingleType) : self
    {
        return new self($converterForSingleType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): array
    {
        $collection = [];
        foreach ($source as $element) {
            $collection[] = $this->converterForSingleType->convert(
                $element,
                $sourceType,
                $sourceMediaType,
                $targetType,
                $targetMediaType
            );
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isCollection() && $targetType->isCollection()
            && $sourceType->isSingleTypeCollection() && $targetType->isSingleTypeCollection()
            && $this->converterForSingleType->matches(
                $sourceType->resolveGenericTypes()[0], $sourceMediaType, $targetType->resolveGenericTypes()[0], $targetMediaType
            );
    }
}