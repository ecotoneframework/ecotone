<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class ReferenceConverter implements ParameterConverter
{
    public function __construct(
        private ExpressionEvaluationService $expressionEvaluationService,
        private object $service,
        private ?string $expression
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): mixed
    {
        if ($this->expression === null) {
            return $this->service;
        }

        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                'service' => $this->service,
                'headers' => $message->getHeaders()->headers(),
                'payload' => $message->getPayload(),
            ],
        );
    }
}
