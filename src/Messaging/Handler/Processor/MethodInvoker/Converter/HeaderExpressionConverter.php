<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

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
    public function __construct(private ExpressionEvaluationService $expressionEvaluationService, private string $headerName, private string $expression, private bool $isRequired)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if ($this->isRequired && ! $message->getHeaders()->containsKey($this->headerName)) {
            throw InvalidArgumentException::create("Header with key {$this->headerName} does not exists for Header Parameter Converter");
        }

        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                'value' => $message->getHeaders()->containsKey($this->headerName) ? $message->getHeaders()->get($this->headerName) : null,
                'headers' => $message->getHeaders()->headers(),
                'payload' => $message->getPayload(),
            ],
        );
    }
}
