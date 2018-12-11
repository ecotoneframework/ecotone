<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

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
class ExpressionBuilder implements ParameterConverterBuilder
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
     * ExpressionBuilder constructor.
     * @param string $parameterName
     * @param string $expression
     */
    private function __construct(string $parameterName, string $expression)
    {
        $this->parameterName = $parameterName;
        $this->expression = $expression;
    }

    /**
     * @param string $parameterName
     * @param string $expression
     * @return ExpressionBuilder
     */
    public static function create(string $parameterName, string $expression): self
    {
        return new self($parameterName, $expression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        /** @var ExpressionEvaluationService $expressionService */
        $expressionService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);
        Assert::isSubclassOf($expressionService, ExpressionEvaluationService::class, "You're using expression converter parameter, so you must define reference service " . ExpressionEvaluationService::REFERENCE . " in your registry container, which is subclass of " . ExpressionEvaluationService::class);


        return new ExpressionConverter(
            $referenceSearchService,
            $expressionService,
            $this->parameterName,
            $this->expression
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