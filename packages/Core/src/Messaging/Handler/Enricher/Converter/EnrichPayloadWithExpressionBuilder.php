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
 * Class ExpressionSetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichPayloadWithExpressionBuilder implements PropertyEditorBuilder
{
    private string $propertyPath;
    private string $expression;
    private string $mappingExpression;
    private string $nullResultExpression = "";

    /**
     * ExpressionSetterBuilder constructor.
     *
     * @param string $propertyPath
     * @param string $expression
     * @param string $mappingExpression
     */
    private function __construct(string $propertyPath, string $expression, string $mappingExpression)
    {
        $this->propertyPath = $propertyPath;
        $this->expression   = $expression;
        $this->mappingExpression = $mappingExpression;
    }

    /**
     * @param string $propertyPath
     * @param string $expression
     *
     * @return EnrichPayloadWithExpressionBuilder
     */
    public static function createWith(string $propertyPath, string $expression) : self
    {
        return new self($propertyPath, $expression, "");
    }

    /**
     * @param string $nullResultExpression
     * @return EnrichPayloadWithExpressionBuilder
     */
    public function withNullResultExpression(string $nullResultExpression) : self
    {
        $this->nullResultExpression = $nullResultExpression;

        return $this;
    }

    /**
     * Enrich multiple paths
     *
     * @param string $propertyPath path to enriched context e.g. [orders][*][person]
     * @param string $expression should return array, that will be mapped to set in property path e.g. payload
     * @param string $mappingExpression when evaluates to true, then specific element is put in property path e.g. requestContext['personId'] == replyContext['personId']
     * @return EnrichPayloadWithExpressionBuilder
     */
    public static function createWithMapping(string $propertyPath, string $expression, string $mappingExpression) : self
    {
        return new self($propertyPath, $expression, $mappingExpression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): PropertyEditor
    {
        /** @var ExpressionEvaluationService $expressionEvaluationService */
        $expressionEvaluationService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);

        return new EnrichPayloadWithExpressionPropertyEditor(
            $expressionEvaluationService,
            $referenceSearchService,
            PropertyEditorAccessor::createWithMapping($referenceSearchService, $this->mappingExpression),
            PropertyPath::createWith($this->propertyPath),
            $this->expression,
            $this->nullResultExpression,
            $this->mappingExpression
        );
    }
}