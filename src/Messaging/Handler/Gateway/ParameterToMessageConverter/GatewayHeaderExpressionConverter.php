<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class GatewayExpressionConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderExpressionConverter implements GatewayParameterConverter
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
     * @var string
     */
    private $headerName;

    /**
     * MessageToExpressionEvaluationParameterConverter constructor.
     *
     * @param ReferenceSearchService $referenceSearchService
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     */
    public function __construct(ReferenceSearchService $referenceSearchService, ExpressionEvaluationService $expressionEvaluationService, string $parameterName, string $headerName, string $expression)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->expression = $expression;
    }


    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                ->setHeader(
                    $this->headerName,
                    $this->expressionEvaluationService->evaluate(
                        $this->expression,
                        [
                            "referenceService" => $this->referenceSearchService,
                            "value" => $methodArgument->value()
                        ]
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