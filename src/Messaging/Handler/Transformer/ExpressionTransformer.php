<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class ExpressionTransformer
 * @package Ecotone\Messaging\Handler\Transformer
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
final class ExpressionTransformer
{
    public function __construct(private string $expression, private ExpressionEvaluationService $expressionEvaluationService)
    {
    }

    /**
     * @param Message $message
     *
     * @return Message
     */
    public function transform(Message $message): Message
    {
        $evaluatedPayload = $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                'payload' => $message->getPayload(),
                'headers' => $message->getHeaders()->headers(),
            ],
        );

        return MessageBuilder::fromMessage($message)
                    ->setPayload($evaluatedPayload)
                    ->build();
    }
}
