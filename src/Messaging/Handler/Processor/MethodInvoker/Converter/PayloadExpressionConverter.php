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
 * Class MessageToExpressionEvaluationConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PayloadExpressionConverter implements ParameterConverter
{
    public function __construct(private ExpressionEvaluationService $expressionEvaluationService, private string $expression, private ExpressionLocation $location, private ArgumentTypeConversion $argumentTypeConversion, private Type $parameterType)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        try {
            return $this->argumentTypeConversion->convertExpressionResult(
                $this->expressionEvaluationService->evaluateWithMessage(
                    $this->expression,
                    $message,
                    ['value' => $message->getPayload()],
                ),
                $this->parameterType,
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
