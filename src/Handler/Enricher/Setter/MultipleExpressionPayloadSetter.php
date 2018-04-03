<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnrichException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MultipleExpressionSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultipleExpressionPayloadSetter implements Setter
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
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param DataSetter                  $dataSetter
     * @param PropertyPath         $propertyPath
     * @param string                      $expression
     * @param string                 $pathToEnrichedContext
     * @param string                 $dataMappingExpression
     */
    public function __construct(
        ExpressionEvaluationService $expressionEvaluationService, DataSetter $dataSetter,
        PropertyPath $propertyPath, string $expression,
        string $pathToEnrichedContext, string $dataMappingExpression
    )
    {
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
                "headers" => $replyMessage->getHeaders()->headers()
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
                throw EnrichException::create("Can't enrich message, missing reply to be mapped for {$propertyToSaveUnder->getPath()}");
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