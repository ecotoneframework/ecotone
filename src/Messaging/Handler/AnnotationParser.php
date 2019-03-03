<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface AnnotationParser
 * @package SimplyCodedSoftware\Messaging\Handler
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
}