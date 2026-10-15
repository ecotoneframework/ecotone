<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Handler\ParameterConverter;
use Throwable;

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
        private ?string $expression,
        private ExpressionLocation $location,
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

        try {
            return $this->expressionEvaluationService->evaluateWithMessage(
                $this->expression,
                $message,
                ['service' => $this->service],
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
