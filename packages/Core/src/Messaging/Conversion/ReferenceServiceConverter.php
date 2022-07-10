<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ReferenceConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverter implements Converter
{
    private object $object;
    private string $method;
    private \Ecotone\Messaging\Handler\Type $sourceType;
    private \Ecotone\Messaging\Handler\Type $targetType;

    /**
     * ReferenceConverter constructor.
     * @param object $object
     * @param string $method
     * @param Type $sourceType
     * @param Type $targetType
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct($object, string $method, Type $sourceType, Type $targetType)
    {
        Assert::isObject($object, "");
        $this->object = $object;
        $this->method = $method;
        $this->sourceType = $sourceType;
        $this->targetType = $targetType;
    }

    /**
     * @param $object
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @return ReferenceServiceConverter
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create($object, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType) : self
    {
        return new self($object, $method, $sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return call_user_func([$this->object, $this->method], $source);
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $sourceType->equals($this->sourceType)
            && $targetType->equals($this->targetType);
    }
}