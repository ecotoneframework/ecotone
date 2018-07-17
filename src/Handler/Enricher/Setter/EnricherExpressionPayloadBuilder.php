<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherExpressionPayloadBuilder implements SetterBuilder
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
     * @return EnricherExpressionPayloadBuilder
     */
    public static function createWith(string $propertyPath, string $expression) : self
    {
        return new self($propertyPath, $expression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Setter
    {
        /** @var ExpressionEvaluationService $expressionEvaluationService */
        $expressionEvaluationService = $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE);

        return new EnricherExpressionPayloadSetter(
            $expressionEvaluationService,
            DataSetter::create(),
            PropertyPath::createWith($this->propertyPath),
            $this->expression
        );
    }
}