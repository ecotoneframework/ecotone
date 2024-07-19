<?php

namespace Ecotone\Messaging\Handler;

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
}
