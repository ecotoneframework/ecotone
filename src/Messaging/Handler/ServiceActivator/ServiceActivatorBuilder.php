<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\ServiceActivator;

use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterceptableHandler;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\WrapWithMessageBuildProcessor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class ServiceActivatorFactory
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
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
     * @var array|\SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder[]
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
    public function withPassThroughMessageOnVoidInterface(bool $shouldPassThroughMessage) : self
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
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->objectToInvokeReferenceName
            ?   $interfaceToCallRegistry->getForReferenceName($this->objectToInvokeReferenceName, $this->methodName)
            :   $interfaceToCallRegistry->getFor($this->directObjectReference, $this->methodName);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReference(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [
            $this->directObjectReference
                ? $interfaceToCallRegistry->getFor($this->directObjectReference, $this->methodName)
                : $interfaceToCallRegistry->getForReferenceName($this->objectToInvokeReferenceName, $this->methodName)
        ];
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
            $objectToInvoke = $this->directObjectReference ? $this->directObjectReference : $referenceSearchService->get($this->objectToInvokeReferenceName);
        }
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($objectToInvoke, $this->methodName);

        $methodToInvoke = WrapWithMessageBuildProcessor::createWith(
            $objectToInvoke,
            $this->methodName,
            MethodInvoker::createWithInterceptors(
                $objectToInvoke,
                $this->methodName,
                $this->methodParameterConverterBuilders,
                $referenceSearchService,
                $this->orderedAroundInterceptors
            ),
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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