<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ExpressionTransformer
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class ExpressionTransformer
{
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var string
     */
    private $expression;

    /**
     * ExpressionTransformer constructor.
     *
     * @param string                      $expression
     * @param ExpressionEvaluationService $expressionEvaluationService
     */
    public function __construct(string $expression, ExpressionEvaluationService $expressionEvaluationService)
    {
        $this->expression = $expression;
        $this->expressionEvaluationService = $expressionEvaluationService;
    }

    /**
     * @param Message $message
     *
     * @return Message
     */
    public function transform(Message $message) : Message
    {
        $evaluatedPayload = $this->expressionEvaluationService->evaluate($this->expression, [
            "payload" => $message->getPayload(),
            "headers" => $message->getHeaders()->headers()
        ]);

        return MessageBuilder::fromMessage($message)
                    ->setPayload($evaluatedPayload)
                    ->build();
    }
}