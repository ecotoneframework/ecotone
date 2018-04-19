<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverter;
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
     * @var MessageToParameterConverterBuilder[]|array
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];

    /**
     * TransformerBuilder constructor.
     * @param string $inputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     */
    private function __construct(string $inputChannelName, string $objectToInvokeReference, string $methodName)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeReference;
        $this->methodName = $methodName;

        $this->withInputMessageChannel($inputChannelName);
    }

    /**
     * @param string $inputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @return TransformerBuilder
     */
    public static function create(string $inputChannelName, string $objectToInvokeReference, string $methodName): self
    {
        return new self($inputChannelName, $objectToInvokeReference, $methodName);
    }

    /**
     * @param string $inputChannelName
     * @param array|string[] $messageHeaders
     * @return TransformerBuilder
     */
    public static function createHeaderEnricher(string $inputChannelName, array $messageHeaders) : self
    {
        $transformerBuilder = new self($inputChannelName, "", "transform");
        $transformerBuilder->setDirectObjectToInvoke(HeaderEnricher::create($messageHeaders));

        return $transformerBuilder;
    }

    /**
     * @param string $inputChannelName
     * @param object $referenceObject
     * @param string $methodName
     *
     * @return TransformerBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithReferenceObject(string $inputChannelName, $referenceObject, string $methodName) : self
    {
        Assert::isObject($referenceObject, "Reference object for transformer must be object");

        $transformerBuilder = new self($inputChannelName,  "", $methodName);
        $transformerBuilder->setDirectObjectToInvoke($referenceObject);

        return $transformerBuilder;
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
     * @param array|MessageToParameterConverter[] $methodParameterConverterBuilders
     *
     * @return TransformerBuilder
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

       $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

       return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
    {
        $objectToInvokeOn = $this->object ? $this->object : $referenceSearchService->findByReference($this->objectToInvokeReferenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->hasReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $methodParameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }

        return new Transformer(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                TransformerMessageProcessor::createFrom(
                    MethodInvoker::createWith(
                        $objectToInvokeOn,
                        $this->methodName,
                        $methodParameterConverters
                    )
                ),
                $channelResolver,
                false
            )
        );
    }



    public function __toString()
    {
        return "transformer";
    }

    /**
     * @param object $objectToInvoke
     */
    private function setDirectObjectToInvoke($objectToInvoke) : void
    {
        $this->object = $objectToInvoke;
    }
}