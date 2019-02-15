<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionHeaderSetterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichHeaderWithExpressionBuilder implements PropertyEditorBuilder
{
    /**
     * @var string
     */
    private $propertyPath;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var string
     */
    private $nullResultExpression = "";

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