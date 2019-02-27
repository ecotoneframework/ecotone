<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Aspect;

/**
 * Class Advice
 * @package SimplyCodedSoftware\Messaging\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Advice
{
    /** @var string Around advice: Advice that surrounds a join point such as a method invocation. This is the most powerful kind of advice. Around advice can perform custom behavior before and after the method invocation. It is also responsible for choosing whether to proceed to the join point or to shortcut the advised method execution by returning its own return value or throwing an exception. */
    private const AROUND = "around";
    /** @var string Before advice: Advice that executes before a join point, but which does not have the ability to prevent execution flow proceeding to the join point (unless it throws an exception). */
    private const BEFORE = "before";
    /** @var string After (finally) advice: Advice to be executed regardless of the means by which a join point exits (normal or exceptional return). */
    private const AFTER = "after";
    /** @var string After returning advice: Advice to be executed after a join point completes normally: for example, if a method returns without throwing an exception. */
    private const AFTER_RETURNING = "afterReturning";
    /** @var string After throwing advice: Advice to be executed if a method exits by throwing an exception. */
    private const AFTER_THROWING = "afterThrowing";

    /**
     * @var string
     */
    private $type;

    /**
     * Advice constructor.
     * @param string $type
     */
    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return Advice
     */
    public static function createAround() : self
    {
        return new self(self::AROUND);
    }

    /**
     * @return Advice
     */
    public static function createAfter() : self
    {
        return new self(self::AFTER);
    }

    /**
     * @return Advice
     */
    public static function createBefore() : self
    {
        return new self(self::BEFORE);
    }
}