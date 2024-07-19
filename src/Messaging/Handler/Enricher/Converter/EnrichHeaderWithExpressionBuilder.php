<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorBuilder;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;

/**
 * Class ExpressionHeaderSetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EnrichHeaderWithExpressionBuilder implements PropertyEditorBuilder
{
    private string $propertyPath;
    private string $expression;
    private string $nullResultExpression = '';

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
    public static function createWith(string $propertyPath, string $expression): self
    {
        return new self($propertyPath, $expression);
    }

    /**
     * @param string $nullResultExpression
     * @return EnrichHeaderWithExpressionBuilder
     */
    public function withNullResultExpression(string $nullResultExpression): self
    {
        $this->nullResultExpression = $nullResultExpression;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(EnrichHeaderWithExpressionPropertyEditor::class, [
            new Reference(ExpressionEvaluationService::REFERENCE),
            new Reference(PropertyEditorAccessor::class),
            new Definition(PropertyPath::class, [$this->propertyPath], 'createWith'),
            $this->nullResultExpression,
            $this->expression,
        ]);
    }
}
