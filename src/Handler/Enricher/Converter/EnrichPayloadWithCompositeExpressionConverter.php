<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnrichException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MultipleExpressionSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichPayloadWithCompositeExpressionConverter implements EnricherConverter
{
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var DataSetter
     */
    private $dataSetter;
    /**
     * @var PropertyPath
     */
    private $propertyPath;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var string
     */
    private $pathToEnrichedContext;
    /**
     * @var string
     */
    private $dataMappingExpression;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * ExpressionSetter constructor.
     *
     * @param ReferenceSearchService $referenceSearchService
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param DataSetter $dataSetter
     * @param PropertyPath $propertyPath
     * @param string $expression
     * @param string $pathToEnrichedContext
     * @param string $dataMappingExpression
     */
    public function __construct(
        ReferenceSearchService $referenceSearchService, ExpressionEvaluationService $expressionEvaluationService,
        DataSetter $dataSetter, PropertyPath $propertyPath, string $expression,
        string $pathToEnrichedContext, string $dataMappingExpression
    )
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->dataSetter                  = $dataSetter;
        $this->propertyPath                = $propertyPath;
        $this->expression                  = $expression;
        $this->pathToEnrichedContext       = $pathToEnrichedContext;
        $this->dataMappingExpression       = $dataMappingExpression;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $dataToBeEnriched   = $enrichMessage->getPayload();
        $elementsFromReplyMessage = $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                "payload" => $replyMessage->getPayload(),
                "headers" => $replyMessage->getHeaders()->headers(),
                "referenceService" => $this->referenceSearchService
            ]
        );

        $contexts = $dataToBeEnriched;
        if ($this->pathToEnrichedContext) {
            $contexts = $this->expressionEvaluationService->evaluate($this->pathToEnrichedContext, $dataToBeEnriched);
        }

        foreach ($contexts as $key => $context) {
            $propertyToSaveUnder = PropertyPath::createWith($this->pathToEnrichedContext . "[{$key}][{$this->propertyPath}]");
            $hasBeenEnriched = false;
            foreach ($elementsFromReplyMessage as $dataToEnrich) {
                if ($this->expressionEvaluationService->evaluate($this->dataMappingExpression, ["context" => $context, "reply" => $dataToEnrich]) === true) {
                    $dataToBeEnriched    = $this->dataSetter->enrichDataWith($propertyToSaveUnder, $dataToBeEnriched, $dataToEnrich);
                    $hasBeenEnriched = true;
                    break;
                }
            }

            if (!$hasBeenEnriched) {
                throw EnrichException::create("Can't enrich message, missing reply to be mapped for {$propertyToSaveUnder->getPath()}. Message to enrich: {$enrichMessage}, Message to enrich with: {$replyMessage}");
            }
        }

        return $dataToBeEnriched;
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}