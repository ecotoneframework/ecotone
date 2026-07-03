<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class StubExpressionEvaluationAdapter implements ExpressionEvaluationService
{
    public function evaluate(string $expression, array $evaluationContext)
    {
        throw new InvalidArgumentException('Missing Symfony Expression Language, add `symfony/expression-language` in order to use expressions');
    }

    public function evaluateWithMessage(string $expression, Message $message, array $additionalContext = []): mixed
    {
        return $this->evaluate($expression, $additionalContext);
    }

    public function evaluateWithContext(string $expression, array $context): mixed
    {
        return $this->evaluate($expression, $context);
    }
}
