<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class GatewayExpressionConverter
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayPayloadExpressionConverter implements GatewayParameterConverter
{
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**s
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
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                ->setPayload(
                    $this->expressionEvaluationService->evaluate(
                        $this->expression,
                        [
                            "value" => $methodArgument->value()
                        ],
                        $this->referenceSearchService
                    )
                );
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return $methodArgument->getParameterName() === $this->parameterName;
    }
}