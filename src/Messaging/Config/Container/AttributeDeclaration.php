<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container;

use function array_merge;

use Ecotone\AnnotationFinder\TypeResolver;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionParameter;

/**
 * licence Apache-2.0
 */
final class AttributeDeclaration
{
    public function __construct(
        private string $attributeClassName,
        private string $className,
        private ?string $methodName = null,
        private ?string $parameterName = null,
        private int $indexAmongSameAttributes = 0,
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    public function toAttributeDefinition(): AttributeDefinition
    {
        return new AttributeDefinition(
            $this->attributeClassName,
            [$this->attributeClassName, $this->className, $this->methodName, $this->parameterName, $this->indexAmongSameAttributes],
            [self::class, 'resolveAttributeInstance'],
            $this,
        );
    }

    public static function resolveAttributeInstance(string $attributeClassName, string $className, ?string $methodName, ?string $parameterName, int $indexAmongSameAttributes): object
    {
        return self::declaredAttributes($attributeClassName, $className, $methodName, $parameterName)[$indexAmongSameAttributes]->newInstance();
    }

    /**
     * @return ReflectionAttribute[]
     */
    private static function declaredAttributes(string $attributeClassName, string $className, ?string $methodName, ?string $parameterName): array
    {
        if ($parameterName !== null) {
            return (new ReflectionParameter([$className, $methodName], $parameterName))->getAttributes($attributeClassName);
        }

        $reflectionClass = new ReflectionClass($className);
        if ($methodName !== null) {
            return TypeResolver::getMethodOwnerClass($reflectionClass, $methodName)->getMethod($methodName)->getAttributes($attributeClassName);
        }

        $attributes = [];
        while ($reflectionClass) {
            $attributes = array_merge($attributes, $reflectionClass->getAttributes($attributeClassName));
            $reflectionClass = $reflectionClass->getParentClass();
        }

        return $attributes;
    }
}
