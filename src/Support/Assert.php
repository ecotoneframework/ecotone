<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Support;

/**
 * Class Assert
 * @package SimplyCodedSoftware\IntegrationMessaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Assert
{
    /**
     * @param $valueToCheck
     * @param string $exceptionMessage
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function allInstanceOfType(array $arrayToCheck, string $className) : void
    {
        foreach ($arrayToCheck as $classToCompare) {
            Assert::isSubclassOf($classToCompare, $className, "");
        }
    }

    /**
     * @param $objectToCheck
     * @param string $className
     * @param string $message
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function isSubclassOf($objectToCheck, string $className, string $message) : void
    {
        $classToCheck = get_class($objectToCheck);
        if ($classToCheck !== $className && !is_subclass_of($objectToCheck, $className)) {
            throw InvalidArgumentException::create("{$message}. Passed argument should be of type {$className} and got {$classToCheck}.");
        }
    }

    /**
     * @param string $interfaceToCheck
     * @param string $message
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function isObject($valueToCheck, string $exceptionMessage) : void
    {
        if (!is_object($valueToCheck)) {
            throw InvalidArgumentException::create($exceptionMessage);
        }
    }
}