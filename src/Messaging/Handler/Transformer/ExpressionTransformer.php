<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Support\MessageBuilder;
use Throwable;

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
    public function __construct(private string $expression, private ExpressionEvaluationService $expressionEvaluationService, private ExpressionLocation $location)
    {
    }

    /**
     * @param Message $message
     *
     * @return Message
     */
    public function transform(Message $message): Message
    {
        try {
            $evaluatedPayload = $this->expressionEvaluationService->evaluate(
                $this->expression,
                [
                    'payload' => $message->getPayload(),
                    'headers' => $message->getHeaders()->headers(),
                ],
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }

        return MessageBuilder::fromMessage($message)
                    ->setPayload($evaluatedPayload)
                    ->build();
    }
}
