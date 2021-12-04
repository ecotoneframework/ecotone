<?php

namespace Ecotone\AnnotationFinder\AnnotationResolver;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\AnnotationFinder\TypeResolver;

class AttributeResolver implements AnnotationResolver
{
    /**
     * @inheritDoc
     */
    public function getAnnotationsForMethod(string $className, string $methodName): array
    {
        try {
            $reflectionMethod = TypeResolver::getMethodOwnerClass(new \ReflectionClass($className), $methodName)->getMethod($methodName);

            return array_reduce($reflectionMethod->getAttributes(), function(array $carry, \ReflectionAttribute $attribute) {
                if (!class_exists($attribute->getName())) {
                    return $carry;
                }

                $carry[] = $attribute->newInstance();

                return $carry;
            }, []);
        } catch (\ReflectionException $e) {
            throw ConfigurationException::create("Class {$className} with method {$methodName} does not exists or got annotation configured wrong: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForClass(string $className): array
    {
        return array_reduce((new \ReflectionClass($className))->getAttributes(), function(array $carry, \ReflectionAttribute $attribute) {
            if (!class_exists($attribute->getName())) {
                return $carry;
            }

            $carry[] = $attribute->newInstance();

            return $carry;
        }, []);
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array
    {
        $reflectionClass = new \ReflectionClass($className);
        $parentClass = $reflectionClass;

        do {
            foreach ($parentClass->getProperties() as $property) {
                if ($property->getName() !== $propertyName) {
                    continue;
                }

                return array_reduce((new \ReflectionProperty($className, $propertyName))->getAttributes(), function(array $carry, \ReflectionAttribute $attribute) {
                    if (!class_exists($attribute->getName())) {
                        return $carry;
                    }

                    $carry[] = $attribute->newInstance();

                    return $carry;
                }, []);
            }
        }while($parentClass = $parentClass->getParentClass());

        /** @phpstan-ignore-next-line */
        ConfigurationException::create("Can't resolve property {$propertyName} for class {$className}");
    }
}