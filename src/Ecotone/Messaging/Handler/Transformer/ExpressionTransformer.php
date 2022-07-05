<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class ExpressionTransformer
 * @package Ecotone\Messaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class ExpressionTransformer
{
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    private string $expression;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;

    /**
     * ExpressionTransformer constructor.
     *
     * @param string $expression
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     */
    public function __construct(string $expression, ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService)
    {
        $this->expression = $expression;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param Message $message
     *
     * @return Message
     */
    public function transform(Message $message) : Message
    {
        $evaluatedPayload = $this->expressionEvaluationService->evaluate($this->expression,
            [
                "payload" => $message->getPayload(),
                "headers" => $message->getHeaders()->headers()
            ],
            $this->referenceSearchService
        );

        return MessageBuilder::fromMessage($message)
                    ->setPayload($evaluatedPayload)
                    ->build();
    }
}