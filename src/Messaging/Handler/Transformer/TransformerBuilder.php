<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Transformer;
use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private string $objectToInvokeReferenceName;
    /**
     * @var object
     */
    private $directObject;
    private string $methodName;
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    private ?string $expression = null;

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
            $this->requiredReferenceNames[] = $objectToInvokeReference;
        }
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        if ($this->expression) {
            $interfaceToCallRegistry->getFor(ExpressionTransformer::class, "transform");
            return [];
        }

        return [
            $this->directObject
                ? $interfaceToCallRegistry->getFor($this->directObject, $this->methodName)
                : $interfaceToCallRegistry->getForReferenceName($this->objectToInvokeReferenceName, $this->methodName)
        ];
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
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWithDirectObject($referenceObject, string $methodName) : self
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
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        if ($this->expression) {
            return $interfaceToCallRegistry->getFor(ExpressionTransformer::class, "transform");
        }

        return $this->objectToInvokeReferenceName ? $interfaceToCallRegistry->getForReferenceName($this->objectToInvokeReferenceName, $this->methodName) : $interfaceToCallRegistry->getFor($this->directObject, $this->methodName);
    }

    /**
     * @param array|ParameterConverter[] $methodParameterConverterBuilders
     *
     * @return TransformerBuilder
     * @throws \Ecotone\Messaging\MessagingException
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

            $this->directObject = new ExpressionTransformer($this->expression, $expressionEvaluationService, $referenceSearchService);
        }

        $objectToInvokeOn = $this->directObject ? $this->directObject : $referenceSearchService->get($this->objectToInvokeReferenceName);
        /** @var InterfaceToCallRegistry $interfaceCallRegistry */
        $interfaceCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);
        $interfaceToCall = $interfaceCallRegistry->getFor($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->canReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return new Transformer(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith(
                        $interfaceToCall,
                        $objectToInvokeOn,
                        $this->methodParameterConverterBuilders,
                        $referenceSearchService,
                        $channelResolver,
                        $this->orderedAroundInterceptors,
                        $this->getEndpointAnnotations()
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
        $this->directObject = $objectToInvoke;
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
        $reference = $this->objectToInvokeReferenceName ? $this->objectToInvokeReferenceName : get_class($this->directObject);

        return sprintf("Transformer - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}