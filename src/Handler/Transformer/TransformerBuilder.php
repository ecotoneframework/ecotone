<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var string
     */
    private $objectToInvokeReferenceName;
    /**
     * @var object
     */
    private $object;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var ParameterConverterBuilder[]|array
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];
    /**
     * @var string
     */
    private $expression;

    /**
     * TransformerBuilder constructor.
     * @param string $objectToInvokeReference
     * @param string $methodName
     */
    private function __construct(string $objectToInvokeReference, string $methodName)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeReference;
        $this->methodName = $methodName;
    }

    /**
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @return TransformerBuilder
     */
    public static function create(string $objectToInvokeReference, string $methodName): self
    {
        return new self($objectToInvokeReference, $methodName);
    }

    /**
     * @param array|string[] $messageHeaders
     * @return TransformerBuilder
     */
    public static function createHeaderEnricher(array $messageHeaders) : self
    {
        $transformerBuilder = new self( "", "transform");
        $transformerBuilder->setDirectObjectToInvoke(HeaderEnricher::create($messageHeaders));

        return $transformerBuilder;
    }

    /**
     * @param object $referenceObject
     * @param string $methodName
     *
     * @return TransformerBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithReferenceObject($referenceObject, string $methodName) : self
    {
        Assert::isObject($referenceObject, "Reference object for transformer must be object");

        $transformerBuilder = new self(  "", $methodName);
        $transformerBuilder->setDirectObjectToInvoke($referenceObject);

        return $transformerBuilder;
    }

    /**
     * Replace payload with result of expression evaluation
     *
     * @param string $expression
     *
     * @return TransformerBuilder
     */
    public static function createWithExpression(string $expression) : self
    {
        $transformerBuilder = new self( "", "transform");

        return $transformerBuilder->setExpression($expression);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        $requiredReferenceNames = $this->requiredReferenceNames;
        $requiredReferenceNames[] = $this->objectToInvokeReferenceName;

        return $requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferenceNames[] = $referenceName;
    }

    /**
     * @param array|ParameterConverter[] $methodParameterConverterBuilders
     *
     * @return TransformerBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

       $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

       return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
    {
        if ($this->expression) {
            $expressionEvaluationService = $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE);
            /** @var ExpressionEvaluationService $expressionEvaluationService */
            Assert::isSubclassOf($expressionEvaluationService, ExpressionEvaluationService::class, "Expected expression service " . ExpressionEvaluationService::REFERENCE . " but got something else.");

            $this->object = new ExpressionTransformer($this->expression, $expressionEvaluationService);
        }

        $objectToInvokeOn = $this->object ? $this->object : $referenceSearchService->findByReference($this->objectToInvokeReferenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->hasReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return new Transformer(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith(
                        $objectToInvokeOn,
                        $this->methodName,
                        $this->methodParameterConverterBuilders,
                        $referenceSearchService
                    )
                ),
                $channelResolver,
                false
            )
        );
    }

    /**
     * @param object $objectToInvoke
     */
    private function setDirectObjectToInvoke($objectToInvoke) : void
    {
        $this->object = $objectToInvoke;
    }

    /**
     * @param string $expression
     *
     * @return TransformerBuilder
     */
    private function setExpression(string $expression) : self
    {
        $this->expression = $expression;

        return $this;
    }

    public function __toString()
    {
        $reference = $this->objectToInvokeReferenceName ? $this->objectToInvokeReferenceName : get_class($this->object);

        return sprintf("Transformer - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getName(), $this->getInputMessageChannelName());
    }
}