<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ExpressionBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadExpressionBuilder implements ParameterConverterBuilder
{
    private string $parameterName;
    private string $expression;

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
     * @return HeaderExpressionBuilder
     */
    public static function create(string $parameterName, string $expression): self
    {
        return new self($parameterName, $expression);
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
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        /** @var ExpressionEvaluationService $expressionService */
        $expressionService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);
        Assert::isSubclassOf($expressionService, ExpressionEvaluationService::class, "You're using expression converter parameter, so you must define reference service " . ExpressionEvaluationService::REFERENCE . " in your registry container, which is subclass of " . ExpressionEvaluationService::class);


        return new PayloadExpressionConverter(
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