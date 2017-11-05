<?php

namespace Messaging\Support;

/**
 * Class Assert
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Assert
{
    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \Messaging\MessagingException
     */
    public static function notNullAndEmpty($valueToCheck, string $exceptionMessage) : void
    {
        if (!$valueToCheck) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }

    /**
     * @param array $arrayToCheck
     * @param string $className
     * @throws \Messaging\MessagingException
     */
    public static function allInstanceOfType(array $arrayToCheck, string $className) : void
    {
        foreach ($arrayToCheck as $element) {
            $classToCompare = get_class($element);
            Assert::isSubclassOf($classToCompare, $className, "");
        }
    }

    /**
     * @param $objectToCheck
     * @param string $className
     * @param string $message
     * @throws \Messaging\MessagingException
     */
    public static function isSubclassOf($objectToCheck, string $className, string $message) : void
    {
        if ($objectToCheck !== $className && !is_subclass_of($objectToCheck, $className)) {
            throw InvalidArgumentException::create("{$message}. Passed argument should be of type {$className} and got {$message}.");
        }
    }

    /**
     * @param string $interfaceToCheck
     * @param string $message
     * @throws \Messaging\MessagingException
     */
    public static function isInterface(string $interfaceToCheck, string $message) : void
    {
        if (!interface_exists($interfaceToCheck)) {
            throw InvalidArgumentException::create($message);
        }
    }

    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \Messaging\MessagingException
     */
    public static function isObject($valueToCheck, string $exceptionMessage) : void
    {
        if (!is_object($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }
}