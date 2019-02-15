<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface ExpressionEvaluationService
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ExpressionEvaluationService
{
    public const REFERENCE = "expressionEvaluationService";

    /**
     * @param string $expression
     * @param array $evaluationContext
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return mixed
     */
    public function evaluate(string $expression, array $evaluationContext, ReferenceSearchService $referenceSearchService);
}