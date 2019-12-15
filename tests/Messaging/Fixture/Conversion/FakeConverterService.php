<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Conversion;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class FakeConverterService
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeConverterService implements Converter
{
    /**
     * @var mixed
     */
    private $data;
    /**
     * @var TypeDescriptor
     */
    private $typeDescriptor;
    /**
     * @var MediaType
     */
    private $mediaType;

    /**
     * FakeConverterService constructor.
     * @param mixed $data
     * @param Type $typeDescriptor
     * @param MediaType $mediaType
     */
    private function __construct($data, Type $typeDescriptor, MediaType $mediaType)
    {
        $this->data = $data;
        $this->typeDescriptor = $typeDescriptor;
        $this->mediaType = $mediaType;
    }

    /***
     * @param $data
     * @param string $requestTypeDescriptor
     * @param string $requestMediaType
     * @return FakeConverterService
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public static function create($data, string $requestTypeDescriptor, string $requestMediaType) : self
    {
        return new self($data, TypeDescriptor::create($requestTypeDescriptor), MediaType::parseMediaType($requestMediaType));
    }

    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->equals($this->typeDescriptor) && $sourceMediaType->isCompatibleWith($this->mediaType);
    }
}