<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation\Interceptor;

use Ecotone\Messaging\Precedence;

/**
 * Class Before
 * @package Ecotone\Messaging\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Before
{
    /**
     * The aspect returning the lower value has the higher precedence.
     *
     * The highest precedence advice runs first "on the way in" (so given two pieces of before advice, the one with highest precedence runs first).
     * "On the way out" from a join point, the highest precedence advice runs last (so given two pieces of after advice, the one with the highest precedence will run second).
     */
    public int $precedence = Precedence::DEFAULT_PRECEDENCE;

    /**
     * Advice is associated with a pointcut expression and runs at any join point matched by the pointcut (for example, the execution of a method with a certain name)
     */
    public string $pointcut = "";
    /**
     * Should the return value change headers. If false, then it replaces message's payload
     */
    public bool $changeHeaders = false;
    public array $parameterConverters = [];
}