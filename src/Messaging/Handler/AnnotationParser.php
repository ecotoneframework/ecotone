<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface AnnotationParser
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationParser
{
    /**
     * @param string $className
     * @param string $methodName
     * @return object[]
     */
    public function getAnnotationsForMethod(string $className, string $methodName) : iterable;

    /**
     * @param string $className
     * @return object[]
     */
    public function getAnnotationsForClass(string $className) : iterable;

    /**
     * @param string $className
     * @param string $propertyName
     * @return object[]
     */
    public function getAnnotationsForProperty(string $className, string $propertyName) : iterable;
}