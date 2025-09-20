<?php

namespace Ecotone\AnnotationFinder\AnnotationResolver;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\AnnotationFinder\TypeResolver;
use Ecotone\Messaging\Attribute\IsAbstract;
use Error;

use function preg_match;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * licence Apache-2.0
 */
class AttributeResolver implements AnnotationResolver
{
    /**
     * @inheritDoc
     */
    public function getAnnotationsForMethod(string $className, string $methodName): array
    {
        try {
            $analyzedClass = new ReflectionClass($className);
            $reflectionMethod = TypeResolver::getMethodOwnerClass($analyzedClass, $methodName)->getMethod($methodName);

            return array_reduce($reflectionMethod->getAttributes(), function (array $carry, ReflectionAttribute $attribute) {
                if (! class_exists($attribute->getName())) {
                    return $carry;
                }

                $carry[] = $attribute->newInstance();

                return $carry;
            }, []);
        } catch (ReflectionException $e) {
            throw ConfigurationException::create("Class {$className} with method {$methodName} does not exists or got annotation configured wrong: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForClass(string $className): array
    {
        $attributes = [];
        $currentClass = new ReflectionClass($className);
        $level = 0;

        while ($currentClass) {
            $currentLevelAttributes = [];
            foreach ($currentClass->getAttributes() as $attribute) {
                if (! class_exists($attribute->getName())) {
                    continue;
                }
                if (in_array($attribute->getName(), array_map(fn ($attr) => $attr::class, $attributes))) {
                    continue; // Avoid duplicate attributes from parent classes
                }
                $currentLevelAttributes[] = $attribute->newInstance();
            }

            $attributes = array_merge($attributes, $currentLevelAttributes);

            if ($level === 0 && $currentClass->isAbstract() && ! $currentClass->isInterface()) {
                $attributes[] = new IsAbstract();
            }

            $currentClass = $currentClass->getParentClass();
            $level++;
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array
    {
        $reflectionClass = new ReflectionClass($className);
        $parentClass = $reflectionClass;

        do {
            foreach ($parentClass->getProperties() as $property) {
                if ($property->getName() !== $propertyName) {
                    continue;
                }

                return array_reduce((new ReflectionProperty($className, $propertyName))->getAttributes(), function (array $carry, ReflectionAttribute $attribute) {
                    if (! class_exists($attribute->getName())) {
                        return $carry;
                    }

                    try {
                        $carry[] = $attribute->newInstance();
                    } catch (Error $e) {
                        if (preg_match('/Attribute "(.*)" cannot target property/', $e->getMessage())) {
                            // Do nothing: it is an attribute targeting a parameter promoted to a property
                        } else {
                            throw $e;
                        }
                    }

                    return $carry;
                }, []);
            }
        } while ($parentClass = $parentClass->getParentClass());

        throw ConfigurationException::create("Can't resolve property {$propertyName} for class {$className}");
    }
}
