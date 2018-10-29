<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ExpressionSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichPayloadWithExpressionConverter implements EnricherConverter
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
    private $mappingExpression;

    /**
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param DataSetter $dataSetter
     * @param PropertyPath $propertyPath
     * @param string $expression
     * @param string $mappingExpression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, DataSetter $dataSetter, PropertyPath $propertyPath, string $expression, string $mappingExpression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->propertyPath                = $propertyPath;
        $this->expression                  = $expression;
        $this->dataSetter = $dataSetter;
        $this->referenceSearchService = $referenceSearchService;
        $this->mappingExpression = $mappingExpression;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $dataToEnrich = $this->expressionEvaluationService->evaluate(
            $this->expression, [
            "payload" => $replyMessage ? $replyMessage->getPayload() : null,
            "headers" => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
            "request" => [
                "payload" => $enrichMessage->getPayload(),
                "headers" => $enrichMessage->getHeaders()
            ],
            "referenceService" => $this->referenceSearchService
        ]);

        return $this->dataSetter->enrichDataWith($this->propertyPath, $enrichMessage->getPayload(), $dataToEnrich, $enrichMessage, $replyMessage);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}