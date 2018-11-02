<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ExpressionHeaderSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichHeaderWithExpressionPropertyEditor implements PropertyEditor
{
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var PropertyPath
     */
    private $propertyPath;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var DataSetter
     */
    private $dataSetter;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var string
     */
    private $nullResultExpression;

    /**
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param DataSetter $dataSetter
     * @param PropertyPath $propertyPath
     * @param string $nullResultExpression
     * @param string $expression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, DataSetter $dataSetter, PropertyPath $propertyPath, string $nullResultExpression, string $expression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->propertyPath                = $propertyPath;
        $this->expression                  = $expression;
        $this->dataSetter = $dataSetter;
        $this->referenceSearchService = $referenceSearchService;
        $this->nullResultExpression = $nullResultExpression;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $evaluateAgainst = $this->canNullExpressionBeUsed($replyMessage) ? $this->nullResultExpression : $this->expression;

        $dataToEnrich = $this->expressionEvaluationService->evaluate(
            $evaluateAgainst,
            [
                "payload" => $replyMessage ? $replyMessage->getPayload() : null,
                "headers" => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
                "request" => [
                    "payload" => $enrichMessage->getPayload(),
                    "headers" => $enrichMessage->getHeaders()
                ],
                "referenceService" => $this->referenceSearchService
            ]
        );

        return $this->dataSetter->enrichDataWith($this->propertyPath, $enrichMessage->getHeaders()->headers(), $dataToEnrich, $enrichMessage, $replyMessage);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return false;
    }

    /**
     * @param null|Message $replyMessage
     * @return bool
     */
    private function canNullExpressionBeUsed(?Message $replyMessage): bool
    {
        return $this->nullResultExpression && !$replyMessage;
    }
}