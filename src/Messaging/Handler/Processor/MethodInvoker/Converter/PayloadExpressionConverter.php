<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;

/**
 * Class MessageToExpressionEvaluationConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadExpressionConverter implements ParameterConverter
{
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    /**s
     * @var string
     */
    private string $parameterName;
    private string $expression;

    /**
     * MessageToExpressionEvaluationParameterConverter constructor.
     *
     * @param ReferenceSearchService $referenceSearchService
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string $parameterName
     * @param string $expression
     */
    public function __construct(ReferenceSearchService $referenceSearchService, ExpressionEvaluationService $expressionEvaluationService, string $parameterName, string $expression)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->parameterName = $parameterName;
        $this->expression = $expression;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                "value" => $message->getPayload(),
                "headers" => $message->getHeaders()->headers(),
                "payload" => $message->getPayload()
            ],
            $this->referenceSearchService
        );
    }
}