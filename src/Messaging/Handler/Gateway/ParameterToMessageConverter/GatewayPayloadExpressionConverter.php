<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class GatewayExpressionConverter
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayPayloadExpressionConverter implements GatewayParameterConverter
{
    private ExpressionEvaluationService $expressionEvaluationService;
    /**s
     * @var string
     */
    private string $parameterName;
    private string $expression;

    /**
     * MessageToExpressionEvaluationParameterConverter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string $parameterName
     * @param string $expression
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
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        Assert::notNull($methodArgument, 'Gateway header converter can only be called with method argument');

        return $messageBuilder
                ->setPayload(
                    $this->expressionEvaluationService->evaluate(
                        $this->expression,
                        [
                            'value' => $methodArgument->value(),
                        ],
                    )
                );
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(?MethodArgument $methodArgument): bool
    {
        return $methodArgument && ($methodArgument->getParameterName() === $this->parameterName);
    }
}
