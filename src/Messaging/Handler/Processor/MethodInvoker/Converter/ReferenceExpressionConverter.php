<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;
use Throwable;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ReferenceExpressionConverter implements ParameterConverter
{
    public function __construct(
        private ExpressionEvaluationService $expressionEvaluationService,
        private object $service,
        private string $expression,
        private ExpressionLocation $location,
        private ArgumentTypeConversion $argumentTypeConversion,
        private Type $parameterType,
    ) {
    }

    public function getArgumentFrom(Message $message): mixed
    {
        try {
            return $this->argumentTypeConversion->convertExpressionResult(
                $this->expressionEvaluationService->evaluateWithMessage(
                    $this->expression,
                    $message,
                    ['service' => $this->service],
                ),
                $this->parameterType,
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
