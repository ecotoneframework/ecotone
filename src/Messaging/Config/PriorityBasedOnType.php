<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\StreamBasedSource;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\Aggregate;

final class PriorityBasedOnType
{
    public const STANDARD_TYPE = 'standard';
    public const AGGREGATE_TYPE = 'aggregate';
    public const PROJECTION_TYPE = 'projection';

    public function __construct(
        private int $number,
        private string $type
    ) {
        Assert::isTrue(
            in_array($type, [self::STANDARD_TYPE, self::AGGREGATE_TYPE, self::PROJECTION_TYPE]),
            "Type {$type} is not supported"
        );
    }

    public static function default(): self
    {
        return new self(Priority::DEFAULT_PRIORITY, self::STANDARD_TYPE);
    }

    public static function fromInterfaceToCall(InterfaceToCall $interfaceToCall): self
    {
        return self::getPriorityBasedOnType($interfaceToCall);
    }

    public static function fromAnnotatedFinding(AnnotatedFinding $annotatedFinding): self
    {
        return self::getPriorityBasedOnType($annotatedFinding);
    }

    private static function getPriorityBasedOnType(InterfaceToCall|AnnotatedFinding $objectToCheck): PriorityBasedOnType
    {
        $type = match (true) {
            $objectToCheck->hasAnnotation(TypeDescriptor::create(StreamBasedSource::class)) => self::PROJECTION_TYPE,
            $objectToCheck->hasAnnotation(TypeDescriptor::create(Aggregate::class)) => self::AGGREGATE_TYPE,
            default => self::STANDARD_TYPE
        };

        return $objectToCheck->hasAnnotation(TypeDescriptor::create(Priority::class))
            ? new self($objectToCheck->getAnnotationsByImportanceOrder(TypeDescriptor::create(Priority::class))[0]->getHeaderValue(), $type)
            : new self(Priority::DEFAULT_PRIORITY, $type);
    }

    public function toAttributeDefinition(): AttributeDefinition
    {
        return new AttributeDefinition(
            self::class,
            [
                $this->number,
                $this->type,
            ]
        );
    }

    public function hasPriority(string $type): bool
    {
        return $this->type === $type;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
