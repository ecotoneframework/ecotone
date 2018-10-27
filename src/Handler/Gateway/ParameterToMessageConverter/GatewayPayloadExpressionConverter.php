<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class GatewayExpressionConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter
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