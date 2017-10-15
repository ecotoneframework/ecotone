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
            if ($classToCompare !== $className && !is_subclass_of($element, $className)) {
                throw InvalidArgumentException::create("Passed argument should be of type {$className} and got {$classToCompare}");
            }
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