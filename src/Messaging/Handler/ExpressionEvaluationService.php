<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;

/**
 * Interface ExpressionEvaluationService
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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

    /**
     * Evaluates Symfony expression with `payload` and `headers` context variables, merged with additional context variables.
     */
    public function evaluateWithMessage(string $expression, Message $message, array $additionalContext = []): mixed;

    /**
     * Evaluates Symfony expression with given context variables.
     */
    public function evaluateWithContext(string $expression, array $context): mixed;
}
