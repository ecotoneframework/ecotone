<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class ServiceActivatorFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $objectToInvokeReferenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var  bool
     */
    private $isReplyRequired = false;
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
    private $directObjectReference;
    /**
     * @var bool
     */
    private $shouldPassThroughMessage = false;

    /**
     * ServiceActivatorBuilder constructor.
     *
     * @param string $objectToInvokeOnReferenceName
     * @param string $methodName
     */
    private function __construct(string $objectToInvokeOnReferenceName, string $methodName)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeOnReferenceName;
        $this->methodName = $methodName;

        if ($objectToInvokeOnReferenceName) {
            $this->registerRequiredReference($objectToInvokeOnReferenceName);
        }
    }

    /**
     * @param string $objectToInvokeOnReferenceName
     * @param string $methodName
     *
     * @return ServiceActivatorBuilder
     */
    public static function create(string $objectToInvokeOnReferenceName, string $methodName): self
    {
        return new self($objectToInvokeOnReferenceName, $methodName);
    }

    /**
     * @param object $directObjectReference
     * @param string $methodName
     *
     * @return ServiceActivatorBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithDirectReference($directObjectReference, string $methodName) : self
    {
        return self::create("", $methodName)
                        ->withDirectObjectReference($directObjectReference);
    }

    /**
     * @param bool $isReplyRequired
     * @return ServiceActivatorBuilder
     */
    public function withRequiredReply(bool $isReplyRequired): self
    {
        $this->isReplyRequired = $isReplyRequired;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * If service is void, message will passed through to next channel
     *
     * @param bool $shouldPassThroughMessage
     * @return ServiceActivatorBuilder
     */
    public function withPassThroughMessage(bool $shouldPassThroughMessage) : self
    {
        $this->shouldPassThroughMessage = $shouldPassThroughMessage;

        return $this;
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
    public function registerRequiredReference(string $referenceName)
    {
        $this->requiredReferenceNames[] = $referenceName;

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
     * @inheritdoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
    {
        $objectToInvoke = $this->objectToInvokeReferenceName;
        if (!$this->isStaticallyCalled()) {
            $objectToInvoke = $this->directObjectReference ? $this->directObjectReference : $referenceSearchService->findByReference($this->objectToInvokeReferenceName);
        }
        $interfaceToCall = InterfaceToCall::createFromUnknownType($objectToInvoke, $this->methodName);

        $methodToInvoke = MethodInvoker::createWith(
            $objectToInvoke,
            $this->methodName,
            $this->methodParameterConverterBuilders,
            $referenceSearchService
        );
        if ($this->shouldPassThroughMessage && $interfaceToCall->hasReturnTypeVoid()) {
            $methodToInvoke = MethodInvoker::createWith(
                new PassThroughService($methodToInvoke),
                "invoke",
                [],
                $referenceSearchService
            );
        }

        return new ServiceActivatingHandler(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                $methodToInvoke,
                $channelResolver,
                $this->isReplyRequired
            )
        );
    }

    /**
     * @return bool
     * @throws \ReflectionException
     */
    private function isStaticallyCalled(): bool
    {
        if (class_exists($this->objectToInvokeReferenceName)) {
            $referenceMethod = new \ReflectionMethod($this->objectToInvokeReferenceName, $this->methodName);

            if ($referenceMethod->isStatic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $object
     *
     * @return ServiceActivatorBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function withDirectObjectReference($object) : self
    {
        Assert::isObject($object, "Direct reference passed to service activator must be object");

        $this->directObjectReference = $object;

        return $this;
    }

    public function __toString()
    {
        $reference = $this->objectToInvokeReferenceName ? $this->objectToInvokeReferenceName : get_class($this->directObjectReference);

        return sprintf("Service Activator - %s:%s with name `%s` for input channel `%s`", $reference, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}