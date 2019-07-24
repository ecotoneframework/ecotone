<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter;

use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class ExpressionBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderExpressionBuilder implements ParameterConverterBuilder
{
    /**
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
     * ExpressionBuilder constructor.
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     * @param bool $isRequired
     */
    private function __construct(string $parameterName, string $headerName, string $expression, bool $isRequired)
    {
        $this->parameterName = $parameterName;
        $this->expression = $expression;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     * @param bool $isRequired
     * @return HeaderExpressionBuilder
     */
    public static function create(string $parameterName, string $headerName, string $expression, bool $isRequired): self
    {
        return new self($parameterName, $headerName, $expression, $isRequired);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        /** @var ExpressionEvaluationService $expressionService */
        $expressionService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);
        Assert::isSubclassOf($expressionService, ExpressionEvaluationService::class, "You're using expression converter parameter, so you must define reference service " . ExpressionEvaluationService::REFERENCE . " in your registry container, which is subclass of " . ExpressionEvaluationService::class);


        return new HeaderExpressionConverter(
            $referenceSearchService,
            $expressionService,
            $this->parameterName,
            $this->headerName,
            $this->expression,
            $this->isRequired
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}