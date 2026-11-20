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
class HeaderExpressionConverter implements ParameterConverter
{
    public function __construct(private ExpressionEvaluationService $expressionEvaluationService, private string $headerName, private string $expression, private bool $isRequired, private ExpressionLocation $location, private ArgumentTypeConversion $argumentTypeConversion, private Type $parameterType)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if ($this->isRequired && ! $message->getHeaders()->containsKey($this->headerName)) {
            throw ExpressionEvaluationException::because($this->location, "Header '{$this->headerName}' is not available in the message, and the parameter does not allow null.");
        }

        try {
            return $this->argumentTypeConversion->convertExpressionResult(
                $this->expressionEvaluationService->evaluateWithMessage(
                    $this->expression,
                    $message,
                    ['value' => $message->getHeaders()->containsKey($this->headerName) ? $message->getHeaders()->get($this->headerName) : null],
                ),
                $this->parameterType,
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
