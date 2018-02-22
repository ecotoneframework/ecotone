<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MessageToExpressionEvaluationConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToExpressionEvaluationParameterConverter implements MessageToParameterConverter
{
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $expression;

    /**
     * MessageToExpressionEvaluationParameterConverter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string                      $parameterName
     * @param string                      $expression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, string $parameterName, string $expression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->parameterName = $parameterName;
        $this->expression = $expression;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(\ReflectionParameter $reflectionParameter): bool
    {
        return $reflectionParameter->getName() === $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                "payload" => $message->getPayload(),
                "headers" => $message->getHeaders()->headers()
            ]
        );
    }
}