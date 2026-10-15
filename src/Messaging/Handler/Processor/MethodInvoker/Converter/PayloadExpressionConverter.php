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
 * Class MessageToExpressionEvaluationConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PayloadExpressionConverter implements ParameterConverter
{
    public function __construct(private ExpressionEvaluationService $expressionEvaluationService, private string $expression, private ExpressionLocation $location)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        try {
            return $this->expressionEvaluationService->evaluateWithMessage(
                $this->expression,
                $message,
                ['value' => $message->getPayload()],
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
