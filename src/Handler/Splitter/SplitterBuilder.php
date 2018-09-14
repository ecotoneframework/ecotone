<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class SplitterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|\SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];
    /**
     * @var object
     */
    private $directObject;

    /**
     * ServiceActivatorBuilder constructor.
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $referenceName, string $methodName)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;

        if ($referenceName) {
            $this->registerRequiredReference($referenceName);
        }
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return SplitterBuilder
     */
    public static function create(string $referenceName, string $methodName): self
    {
        return new self($referenceName, $methodName);
    }

    /**
     * Splits directly from message payload, without using any service
     *
     * @return SplitterBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createMessagePayloadSplitter() : self
    {
        return self::createWithDirectObject(new DirectMessageSplitter(), "split");
    }

    /**
     * @param object $directReferenceObject
     * @param string $methodName
     * @return SplitterBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithDirectObject($directReferenceObject, string $methodName): self
    {
        Assert::isObject($directReferenceObject, "Direct reference must be object");

        $splitterBuilder = new self("", $methodName);
        $splitterBuilder->setDirectObject($directReferenceObject);

        return $splitterBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferenceNames;
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders)
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
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
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $objectToInvokeOn = $this->directObject ? $this->directObject : $referenceSearchService->findByReference($this->referenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvokeOn, $this->methodName);

        if (!$interfaceToCall->doesItReturnArray()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return new Splitter(
            RequestReplyProducer::createRequestAndSplit(
                $this->outputMessageChannelName,
                MethodInvoker::createWith(
                    $objectToInvokeOn,
                    $this->methodName,
                    $this->methodParameterConverterBuilders,
                    $referenceSearchService
                )
                ,
                $channelResolver
            )
        );
    }

    /**
     * @param object $object
     */
    private function setDirectObject($object) : void
    {
        $this->directObject = $object;
    }

    public function __toString()
    {
        $reference = $this->referenceName ? $this->referenceName : get_class($this->directObject);

        return sprintf("Splitter - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}