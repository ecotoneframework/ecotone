<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter;

use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageToExpressionEvaluationConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderExpressionConverter implements ParameterConverter
{
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**s
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var bool
     */
    private $isRequired;

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
                "value" => $message->getHeaders()->containsKey($this->headerName) ? $message->getHeaders()->get($this->headerName) : null
            ],
            $this->referenceSearchService
        );
    }
}