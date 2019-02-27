<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Aspect;

/**
 * Class Before
 * @package SimplyCodedSoftware\Messaging\Annotation\Aspect
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Before
{
    /**
     * The highest precedence advice runs first "on the way in" (so given two pieces of before advice, the one with highest precedence runs first).
     * "On the way out" from a join point, the highest precedence advice runs last (so given two pieces of after advice, the one with the highest precedence will run second).
     *
     * @var int
     */
    public $precedence;
}