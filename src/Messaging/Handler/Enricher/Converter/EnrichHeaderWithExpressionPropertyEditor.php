<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Message;

/**
 * Class ExpressionHeaderSetter
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class EnrichHeaderWithExpressionPropertyEditor implements PropertyEditor
{
    private ExpressionEvaluationService $expressionEvaluationService;
    private PropertyPath $propertyPath;
    private string $expression;
    private PropertyEditorAccessor $dataSetter;
    private string $nullResultExpression;

    /**
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param PropertyEditorAccessor $dataSetter
     * @param PropertyPath $propertyPath
     * @param string $nullResultExpression
     * @param string $expression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, string $nullResultExpression, string $expression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->propertyPath                = $propertyPath;
        $this->expression                  = $expression;
        $this->dataSetter = $dataSetter;
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
                'payload' => $replyMessage ? $replyMessage->getPayload() : null,
                'headers' => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
                'request' => [
                    'payload' => $enrichMessage->getPayload(),
                    'headers' => $enrichMessage->getHeaders(),
                ],
            ],
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
        return $this->nullResultExpression && ! $replyMessage;
    }
}
