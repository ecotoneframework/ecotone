<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;

use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class ReferenceConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceConverter implements Converter
{
    /**
     * @var object
     */
    private $object;
    /**
     * @var string
     */
    private $method;
    /**
     * @var TypeDescriptor
     */
    private $sourceType;
    /**
     * @var TypeDescriptor
     */
    private $targetType;

    /**
     * ReferenceConverter constructor.
     * @param object $object
     * @param string $method
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct($object, string $method, TypeDescriptor $sourceType, TypeDescriptor $targetType)
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT)
            && $sourceType->equals($this->sourceType)
            && $targetType->equals($this->targetType);
    }
}