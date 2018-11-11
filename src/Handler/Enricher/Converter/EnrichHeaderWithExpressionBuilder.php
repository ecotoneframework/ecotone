<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionHeaderSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
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
            PropertyEditorAccessor::create(
                $expressionEvaluationService,
                $referenceSearchService,
                ""
            ),
            PropertyPath::createWith($this->propertyPath),
            $this->nullResultExpression,
            $this->expression
        );
    }
}