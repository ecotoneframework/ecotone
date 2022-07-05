<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;

/**
 * Class ExpressionSetter
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichPayloadWithExpressionPropertyEditor implements PropertyEditor
{
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    private \Ecotone\Messaging\Handler\Enricher\PropertyPath $propertyPath;
    private string $expression;
    private \Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor $dataSetter;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private string $mappingExpression;
    private string $nullResultExpression;

    /**
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param PropertyEditorAccessor $dataSetter
     * @param PropertyPath $propertyPath
     * @param string $expression
     * @param string $nullResultExpression
     * @param string $mappingExpression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, string $expression, string $nullResultExpression, string $mappingExpression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->propertyPath                = $propertyPath;
        $this->expression                  = $expression;
        $this->dataSetter = $dataSetter;
        $this->referenceSearchService = $referenceSearchService;
        $this->mappingExpression = $mappingExpression;
        $this->nullResultExpression = $nullResultExpression;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $evaluateAgainst = $this->canNullExpressionBeUsed($replyMessage) ? $this->nullResultExpression : $this->expression;

        $dataToEnrich = $this->expressionEvaluationService->evaluate(
            $evaluateAgainst, [
            "payload" => $replyMessage ? $replyMessage->getPayload() : null,
            "headers" => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
            "request" => [
                "payload" => $enrichMessage->getPayload(),
                "headers" => $enrichMessage->getHeaders()
                ]
            ],
            $this->referenceSearchService
        );

        return $this->dataSetter->enrichDataWith($this->propertyPath, $enrichMessage->getPayload(), $dataToEnrich, $enrichMessage, $replyMessage);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
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