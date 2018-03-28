<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExpressionSetterBuilder implements SetterBuilder
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $expression;

    /**
     * ExpressionSetterBuilder constructor.
     *
     * @param string $name
     * @param string $expression
     */
    private function __construct(string $name, string $expression)
    {
        $this->name = $name;
        $this->expression = $expression;
    }

    /**
     * @param string $name
     * @param string $expression
     *
     * @return ExpressionSetterBuilder
     */
    public static function createWith(string $name, string $expression) : self
    {
        return new self($name, $expression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Setter
    {
        return new ExpressionSetter(
            $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE),
            $this->name,
            $this->expression
        );
    }
}