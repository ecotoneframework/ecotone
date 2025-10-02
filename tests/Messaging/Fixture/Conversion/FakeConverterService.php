<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;

/**
 * Class FakeConverterService
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class FakeConverterService implements Converter, CompilableBuilder
{
    /**
     * @var mixed
     */
    private $data;
    /**
     * @var Type
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
    public function __construct($data, Type $typeDescriptor, MediaType $mediaType)
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
    public static function create($data, string $requestTypeDescriptor, string $requestMediaType): self
    {
        return new self($data, Type::create($requestTypeDescriptor), MediaType::parseMediaType($requestMediaType));
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->equals($this->typeDescriptor) && $sourceMediaType->isCompatibleWith($this->mediaType);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(self::class, [
            $this->data,
            $this->typeDescriptor,
            $this->mediaType,
        ]);
    }
}
