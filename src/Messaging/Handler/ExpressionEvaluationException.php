<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\MessagingException;
use Throwable;

/**
 * licence Apache-2.0
 */
final class ExpressionEvaluationException extends MessagingException
{
    public static function wrapping(ExpressionLocation $location, Throwable $cause): Throwable
    {
        if ($cause instanceof self) {
            return $cause;
        }

        return new self($location->describeFailureWith($cause->getMessage()), self::WRONG_EXPRESSION_TO_EVALUATE, $cause);
    }

    public static function because(ExpressionLocation $location, string $cause): self
    {
        return new self($location->describeFailureWith($cause), self::WRONG_EXPRESSION_TO_EVALUATE);
    }

    protected static function errorCode(): int
    {
        return self::WRONG_EXPRESSION_TO_EVALUATE;
    }
}
