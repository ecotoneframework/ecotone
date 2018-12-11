<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class ConversionService
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AutoCollectionConversionService implements ConversionService
{

    /**
     * @var Converter[]
     */
    private $converters;

    /**
     * ConversionService constructor.
     * @param Converter[] $converters
     */
    private function __construct(array $converters)
    {
        $this->initialize($converters);
    }

    /**
     * @param Converter[] $converters
     * @return AutoCollectionConversionService
     */
    public static function createWith(array $converters) : self
    {
        return new self($converters);
    }

    /**
     * @return AutoCollectionConversionService
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param mixed $source
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @param MediaType $sourceMediaType
     * @param MediaType $targetMediaType
     * @return mixed
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if (is_null($source)) {
            return $source;
        }

        $converter = $this->getConverter($sourceType, $sourceMediaType, $targetType, $targetMediaType);

        return $converter->convert($source, $sourceType, $sourceMediaType, $targetType, $targetMediaType);
    }

    /**
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @param MediaType $sourceMediaType
     * @param MediaType $targetMediaType
     * @return bool
     */
    public function canConvert(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType) : bool
    {
        return (bool)$this->getConverter($sourceType, $sourceMediaType, $targetType, $targetMediaType);
    }

    /**
     * @param TypeDescriptor $sourceType
     * @param MediaType $sourceMediaType
     * @param TypeDescriptor $targetType
     * @param MediaType $targetMediaType
     * @return Converter|null
     */
    private function getConverter(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType) : ?Converter
    {
        foreach ($this->converters as $converter) {
            if ($converter->matches($sourceType, $sourceMediaType, $targetType, $targetMediaType)) {
                return $converter;
            }
        }

        return null;
    }

    /**
     * @param Converter[] $converters
     */
    private function initialize(array $converters) : void
    {
        $this->converters = $converters;

        foreach ($converters as $converter) {
            $this->converters[] = CollectionConverter::createForConverter($converter);
        }
    }
}