<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Class PropertyReaderAccessor
 * @package Ecotone\Messaging\Handler\Enricher
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PropertyReaderAccessor
{
    /**
     * @param PropertyPath $propertyPath
     * @param mixed $fromData
     *
     * @return bool
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function hasPropertyValue(PropertyPath $propertyPath, $fromData): bool
    {
        try {
            $this->getPropertyValue($propertyPath, $fromData);
        } catch (ReflectionException | InvalidArgumentException $e) {
            //            Handle it without exceptions
            return false;
        }

        return true;
    }

    public static function getDefinition(): Definition
    {
        return new Definition(
            self::class,
            [],
        );
    }

    /**
     * @param PropertyPath $propertyPath
     * @param mixed $fromData
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function getPropertyValue(PropertyPath $propertyPath, $fromData)
    {
        $cutCurrent = $propertyPath->cutCurrentAccessProperty();
        $currentAccessProperty = $propertyPath->getCurrentAccessProperty();

        if ($cutCurrent) {
            return $this->getPropertyValue($cutCurrent, $this->getValueForCurrentState($fromData, $currentAccessProperty));
        }

        return $this->getValueForCurrentState($fromData, $currentAccessProperty);
    }

    /**
     * @param mixed $fromData
     * @param string $currentAccessProperty
     * @return mixed
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    private function getValueForCurrentState($fromData, string $currentAccessProperty)
    {
        if (is_array($fromData)) {
            if (! array_key_exists($currentAccessProperty, $fromData)) {
                throw InvalidArgumentException::create("Can't access property at `{$currentAccessProperty}`");
            }

            return $fromData[$currentAccessProperty];
        } elseif (is_object($fromData)) {
            $getterMethod = 'get' . ucfirst($currentAccessProperty);

            if (method_exists($fromData, $getterMethod)) {
                return call_user_func([$fromData, $getterMethod]);
            } else {
                $objectReflection = new ReflectionClass($fromData);
                $classProperty = $objectReflection->getProperty($currentAccessProperty);

                if ($classProperty->isInitialized($fromData)) {
                    $classProperty->setAccessible(true);
                    return $classProperty->getValue($fromData);
                }
            }
        }

        throw InvalidArgumentException::create("Can't access property at `{$currentAccessProperty}`");
    }
}
