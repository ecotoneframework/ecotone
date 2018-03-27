<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class MessageToExpressionEvaluationConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToExpressionEvaluationParameterConverterBuilder implements MessageToParameterConverterBuilder
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
     * MessageToExpressionEvaluationConverterBuilder constructor.
     *
     * @param string $parameterName
     * @param string $expression
     */
    private function __construct(string $parameterName, string $expression)
    {
        $this->parameterName = $parameterName;
        $this->expression    = $expression;
    }

    /**
     * @param string                                       $parameterName
     * @param string                                       $expression
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilders
     *
     * @return MessageToExpressionEvaluationParameterConverterBuilder
     */
    public static function createWith(string $parameterName, string $expression, MessageHandlerBuilderWithParameterConverters $messageHandlerBuilders): self
    {
        $messageHandlerBuilders->registerRequiredReference(ExpressionEvaluationService::REFERENCE);
        return new self($parameterName, $expression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageToParameterConverter
    {
        /** @var ExpressionEvaluationService $expressionService */
        $expressionService = $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE);
        Assert::isSubclassOf($expressionService, ExpressionEvaluationService::class, "You're using expression converter parameter, so you must define reference service " . ExpressionEvaluationService::REFERENCE . " in your registry container, which is subclass of " . ExpressionEvaluationService::class);

        return new MessageToExpressionEvaluationParameterConverter(
            $expressionService,
            $this->parameterName,
            $this->expression
        );
    }
}