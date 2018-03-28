<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ExpressionSetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ExpressionSetter implements Setter
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
     * ExpressionSetter constructor.
     *
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string                      $name
     * @param string                      $expression
     */
    public function __construct(ExpressionEvaluationService $expressionEvaluationService, string $name, string $expression)
    {
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->name       = $name;
        $this->expression = $expression;
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