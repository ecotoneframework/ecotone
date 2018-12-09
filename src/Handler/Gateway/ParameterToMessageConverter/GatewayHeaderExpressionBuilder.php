<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class GatewayExpressionBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderExpressionBuilder implements GatewayParameterConverterBuilder
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
     * ExpressionBuilder constructor.
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     */
    private function __construct(string $parameterName, string $headerName, string $expression)
    {
        $this->parameterName = $parameterName;
        $this->expression = $expression;
        $this->headerName = $headerName;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     * @return self
     */
    public static function create(string $parameterName, string $headerName, string $expression): self
    {
        return new self($parameterName, $headerName, $expression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): GatewayParameterConverter
    {
        /** @var ExpressionEvaluationService $expressionService */
        $expressionService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);
        Assert::isSubclassOf($expressionService, ExpressionEvaluationService::class, "You're using expression converter parameter, so you must define reference service " . ExpressionEvaluationService::REFERENCE . " in your registry container, which is subclass of " . ExpressionEvaluationService::class);

        return new GatewayHeaderExpressionConverter(
            $referenceSearchService,
            $expressionService,
            $this->parameterName,
            $this->headerName,
            $this->expression
        );
    }
}