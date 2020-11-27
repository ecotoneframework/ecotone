<?php


namespace Ecotone\Messaging\Handler;


class StubExpressionEvaluationAdapter implements ExpressionEvaluationService
{
    public function evaluate(string $expression, array $evaluationContext, ReferenceSearchService $referenceSearchService)
    {
        throw new \InvalidArgumentException("Missing Symfony Expression Language, add `symfony/expression-language` in order to use expressions");
    }
}