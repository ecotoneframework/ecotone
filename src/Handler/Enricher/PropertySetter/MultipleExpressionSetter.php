<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MultipleExpressionSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultipleExpressionSetter implements Setter
{
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var string
     */
    private $mapping;

    /**
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string $name
     * @param string $expression
     * @param string $mapping
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, string $name, string $expression, string $mapping)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->name = $name;
        $this->expression = $expression;
        $this->mapping = $mapping;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $payload = $enrichMessage->getPayload();

        $payload[$this->name] = $this->expressionEvaluationService->evaluate($this->expression, [
            "payload" => $replyMessage->getPayload(),
            "headers" => $replyMessage->getHeaders()->headers()
        ]);

        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}