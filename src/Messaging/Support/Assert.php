<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Config\ConfigurationException;

/**
 * Class Assert
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class Assert
{
    /**
     * @param bool $toCheck
     * @param string $message
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function isTrue(bool $toCheck, string $message, bool $configurationException = false): void
    {
        if (! $toCheck) {
            $configurationException ? throw ConfigurationException::create($message) : throw InvalidArgumentException::create($message);
        }
    }

    /**
     * @param bool $toCheck
     * @param string $message
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function isFalse(bool $toCheck, string $message, bool $configurationException = false): void
    {
        if ($toCheck) {
            throw $configurationException ? ConfigurationException::create($message) : InvalidArgumentException::create($message);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function notNullAndEmpty($valueToCheck, string $exceptionMessage): void
    {
        if (! $valueToCheck) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function keyExists(array $array, $requiredKey, string $exceptionMessage): void
    {
        if (! isset($array[$requiredKey])) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function keyNotExists(array $array, $key, string $exceptionMessage): void
    {
        if (isset($array[$key])) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function assertEmptyArray(array $array, string $exceptionMessage): void
    {
        if (! empty($array)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function notNull($valueToCheck, string $exceptionMessage): void
    {
        if (is_null($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function null($valueToCheck, string $exceptionMessage): void
    {
        if (! is_null($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param array $arrayToCheck
     * @param string $className
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function allInstanceOfType(array $arrayToCheck, string $className): void
    {
        foreach ($arrayToCheck as $classToCompare) {
            Assert::isSubclassOf($classToCompare, $className, '');
        }
    }

    public static function allObjects(array $arrayToCheck, string $exceptionMessage): void
    {
        foreach ($arrayToCheck as $potentialObject) {
            if (! is_object($potentialObject)) {
                throw InvalidArgumentException::create($exceptionMessage);
            }
        }
    }

    /**
     * @param array $arrayToCheck
     * @param string $exceptionMessage
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function allStrings(array $arrayToCheck, string $exceptionMessage = ''): void
    {
        foreach ($arrayToCheck as $value) {
            Assert::isTrue(is_string($value), $exceptionMessage);
        }
    }

    /**
     * @param $objectToCheck
     * @param string $className
     * @param string $message
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function isSubclassOf($objectToCheck, string $className, string $message): void
    {
        $classToCheck = get_class($objectToCheck);
        if ($classToCheck !== $className && ! is_subclass_of($objectToCheck, $className)) {
            throw InvalidArgumentException::create("{$message}. Passed argument should be of type {$className} and got {$classToCheck}.");
        }
    }

    /**
     * @param string $interfaceToCheck
     * @param string $message
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function isInterface(string $interfaceToCheck, string $message): void
    {
        if (! interface_exists($interfaceToCheck)) {
            throw InvalidArgumentException::create($message);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function isObject($valueToCheck, string $exceptionMessage): void
    {
        if (! is_object($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function isIterable($valueToCheck, string $exceptionMessage): void
    {
        if (! is_iterable($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    public static function oneOf(string $valueToCheck, array $allowedValues, string $exceptionMessage): void
    {
        if (! in_array($valueToCheck, $allowedValues, true)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }
}
