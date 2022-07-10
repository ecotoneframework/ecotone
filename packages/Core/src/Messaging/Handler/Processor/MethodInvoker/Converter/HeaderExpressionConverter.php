<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageToExpressionEvaluationConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderExpressionConverter implements ParameterConverter
{
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    /**s
     * @var string
     */
    private string $parameterName;
    private string $expression;
    private string $headerName;
    private bool $isRequired;

    /**
     * MessageToExpressionEvaluationParameterConverter constructor.
     *
     * @param ReferenceSearchService $referenceSearchService
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     * @param bool $isRequired
     */
    public function __construct(ReferenceSearchService $referenceSearchService, ExpressionEvaluationService $expressionEvaluationService, string $parameterName, string $headerName, string $expression, bool $isRequired)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->parameterName = $parameterName;
        $this->expression = $expression;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        if ($this->isRequired && !$message->getHeaders()->containsKey($this->headerName)) {
            throw InvalidArgumentException::create("Header with key {$this->headerName} does not exists for Header Parameter Converter");
        }

        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                "value" => $message->getHeaders()->containsKey($this->headerName) ? $message->getHeaders()->get($this->headerName) : null,
                "headers" => $message->getHeaders()->headers(),
                "payload" => $message->getPayload()
            ],
            $this->referenceSearchService
        );
    }
}