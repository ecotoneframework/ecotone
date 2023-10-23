<?php

namespace Ecotone\Messaging\Handler;

/**
 * Interface ExpressionEvaluationService
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ExpressionEvaluationService
{
    public const REFERENCE = 'expressionEvaluationService';

    /**
     * @param string $expression
     * @param array $evaluationContext
     *
     * @return mixed
     */
    public function evaluate(string $expression, array $evaluationContext);
}
