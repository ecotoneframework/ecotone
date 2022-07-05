<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorBuilder;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionHeaderSetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichHeaderWithExpressionBuilder implements PropertyEditorBuilder
{
    private string $propertyPath;
    private string $expression;
    private string $nullResultExpression = "";

    /**
     * ExpressionSetterBuilder constructor.
     *
     * @param string $propertyPath
     * @param string $expression
     */
    private function __construct(string $propertyPath, string $expression)
    {
        $this->propertyPath = $propertyPath;
        $this->expression   = $expression;
    }

    /**
     * @param string $propertyPath
     * @param string $expression
     *
     * @return self
     */
    public static function createWith(string $propertyPath, string $expression) : self
    {
        return new self($propertyPath, $expression);
    }

    /**
     * @param string $nullResultExpression
     * @return EnrichHeaderWithExpressionBuilder
     */
    public function withNullResultExpression(string $nullResultExpression) : self
    {
        $this->nullResultExpression = $nullResultExpression;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): PropertyEditor
    {
        /** @var ExpressionEvaluationService $expressionEvaluationService */
        $expressionEvaluationService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);

        return new EnrichHeaderWithExpressionPropertyEditor(
            $expressionEvaluationService,
            $referenceSearchService,
            PropertyEditorAccessor::createWithMapping(
                $referenceSearchService,
                ""
            ),
            PropertyPath::createWith($this->propertyPath),
            $this->nullResultExpression,
            $this->expression
        );
    }
}