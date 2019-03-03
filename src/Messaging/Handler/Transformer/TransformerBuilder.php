<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Transformer;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\OrderedAroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

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
     * @var OrderedAroundInterceptorReference[]
     */
    private $orderedAroundInterceptorReferences = [];

    /**
     * TransformerBuilder constructor.
     * @param string $objectToInvokeReference
     * @param string $methodName
     */
    private function __construct(string $objectToInvokeReference, string $methodName)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeReference;
        $this->methodName = $methodName;

        if ($objectToInvokeReference) {
            $this->registerRequiredReference($objectToInvokeReference);
        }
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
    public function registerRequiredReference(string $referenceName)
    {
        $this->requiredReferenceNames[] = $referenceName;

        return $this;
    }

    /**
     * @param OrderedAroundInterceptorReference[] $orderedAroundInterceptorReferences
     * @return self
     */
    public function withOrderedAroundInterceptors(iterable $orderedAroundInterceptorReferences) : self
    {
        usort($orderedAroundInterceptorReferences, function(OrderedAroundInterceptorReference $element, OrderedAroundInterceptorReference $elementToCompare) {
            if ($element->getPrecedence() == $elementToCompare->getPrecedence()) {
                return 0;
            }

            return $element->getPrecedence() > $elementToCompare->getPrecedence() ? 1 : -1;
        });
        $this->orderedAroundInterceptorReferences = $orderedAroundInterceptorReferences;

        return $this;
    }

    /**
     * @param array|ParameterConverter[] $methodParameterConverterBuilders
     *
     * @return TransformerBuilder
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
            $expressionEvaluationService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);
            /** @var ExpressionEvaluationService $expressionEvaluationService */
            Assert::isSubclassOf($expressionEvaluationService, ExpressionEvaluationService::class, "Expected expression service " . ExpressionEvaluationService::REFERENCE . " but got something else.");

            $this->object = new ExpressionTransformer($this->expression, $expressionEvaluationService, $referenceSearchService);
        }

        $objectToInvokeOn = $this->object ? $this->object : $referenceSearchService->get($this->objectToInvokeReferenceName);
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->hasReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        $interceptors = [];
        foreach ($this->orderedAroundInterceptorReferences as $orderedAroundInterceptorReference) {
            $interceptors[] = $orderedAroundInterceptorReference->buildAroundInterceptor($referenceSearchService);
        }

        return new Transformer(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWithInterceptors(
                        $objectToInvokeOn,
                        $this->methodName,
                        $this->methodParameterConverterBuilders,
                        $referenceSearchService,
                        $interceptors
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

        return sprintf("Transformer - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}