<?php

namespace Ecotone\AnnotationFinder;

use ReflectionClass;
use ReflectionMethod;

/**
 * Class TypeResolver
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class TypeResolver
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public static function getMethodOwnerClass(ReflectionClass $analyzedClass, string $methodName): ReflectionClass
    {
        $methodReflection = $analyzedClass->getMethod($methodName);
        $declaringClass = self::getMethodDeclaringClass($analyzedClass, $methodReflection->getName());
        if ($analyzedClass->getName() !== $declaringClass->getName()) {
            return self::getMethodOwnerClass($declaringClass, $methodName);
        }
        foreach ($analyzedClass->getTraits() as $trait) {
            if ($trait->hasMethod($methodReflection->getName()) && ! self::wasTraitOverwritten($methodReflection, $trait)) {
                return self::getMethodOwnerClass($trait, $methodName);
            }
        }

        return $analyzedClass;
    }

    private static function getMethodDeclaringClass(ReflectionClass $analyzedClass, string $methodName): ReflectionClass
    {
        return $analyzedClass->getMethod($methodName)->getDeclaringClass();
    }

    private static function wasTraitOverwritten(ReflectionMethod $methodReflection, ReflectionClass $trait): bool
    {
        return $methodReflection->getFileName() !== $trait->getMethod($methodReflection->getName())->getFileName();
    }
}
