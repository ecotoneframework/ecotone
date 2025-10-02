<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionMethod;

/**
 * Class ReferenceConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ReferenceServiceConverter implements Converter
{
    private object $object;
    private string $method;
    private Type $sourceType;
    private Type $targetType;

    /**
     * ReferenceConverter constructor.
     * @param object $object
     * @param string $method
     * @param Type $sourceType
     * @param Type $targetType
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct($object, string $method, Type $sourceType, Type $targetType)
    {
        Assert::isObject($object, '');
        $this->object = $object;
        $this->method = $method;
        $this->sourceType = $sourceType;
        $this->targetType = $targetType;

        $reflectionMethod = new ReflectionMethod($object, $method);

        if (count($reflectionMethod->getParameters()) !== 1) {
            throw InvalidArgumentException::create("Converter should have only single parameter: {$reflectionMethod}");
        }
    }

    /**
     * @param $object
     * @param string $method
     * @param Type $sourceType
     * @param Type $targetType
     * @return ReferenceServiceConverter
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create($object, string $method, Type $sourceType, Type $targetType): self
    {
        return new self($object, $method, $sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return call_user_func([$this->object, $this->method], $source);
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $sourceType->isCompatibleWith($this->sourceType)
            && $targetType->equals($this->targetType);
    }
}
