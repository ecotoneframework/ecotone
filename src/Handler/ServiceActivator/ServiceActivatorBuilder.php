<?php

namespace SimplyCodedSoftware\Messaging\Handler\ServiceActivator;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class ServiceActivatorFactory
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $objectToInvokeOnReference;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $outputChannelName;
    /**
     * @var  bool
     */
    private $isReplyRequired = false;
    /**
     * @var array|MethodParameterConverter[]
     */
    private $methodArguments = [];
    /**
     * @var string
     */
    private $inputMessageChannelName;
    /**
     * @var string
     */
    private $messageHandlerName;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * ServiceActivatorBuilder constructor.
     * @param string $objectToInvokeOnReference
     * @param string $methodName
     */
    private function __construct(string $objectToInvokeOnReference, string $methodName)
    {
        $this->objectToInvokeOnReference = $objectToInvokeOnReference;
        $this->methodName = $methodName;
    }

    /**
     * @param string $objectToInvokeOnReference
     * @param string $methodName
     * @return ServiceActivatorBuilder
     */
    public static function create(string $objectToInvokeOnReference, string $methodName): self
    {
        return new self($objectToInvokeOnReference, $methodName);
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
     * @param string $messageChannelName
     * @return ServiceActivatorBuilder
     */
    public function withOutputChannel(string $messageChannelName): self
    {
        $this->outputChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @param array|MethodParameterConverter[] $methodArguments
     * @return ServiceActivatorBuilder
     */
    public function withMethodArguments(array $methodArguments): self
    {
        $this->methodArguments = $methodArguments;

        return $this;
    }

    /**
     * @param string $inputMessageChannelName
     * @return ServiceActivatorBuilder
     */
    public function withInputMessageChannel(string $inputMessageChannelName) : self
    {
        $this->inputMessageChannelName = $inputMessageChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        $this->channelResolver = $channelResolver;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService): MessageHandlerBuilder
    {
        $this->referenceSearchService = $referenceSearchService;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputMessageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [$this->objectToInvokeOnReference];
    }

    /**
     * @param string $name
     * @return ServiceActivatorBuilder
     */
    public function withName(string $name) : self
    {
        $this->messageHandlerName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->messageHandlerName;
    }

    /**
     * @return MessageHandler
     */
    public function build(): MessageHandler
    {
        Assert::notNullAndEmpty($this->channelResolver, "You must pass channel resolver to Service Activator Builder");
        Assert::notNullAndEmpty($this->referenceSearchService, "You must pass reference search service");
        $objectToInvoke = $this->referenceSearchService->findByReference($this->objectToInvokeOnReference);

        return new ServiceActivatingHandler(
            RequestReplyProducer::createFrom(
                $this->outputChannelName ? $this->channelResolver->resolve($this->outputChannelName) : null,
                MethodInvoker::createWith(
                    $objectToInvoke,
                    $this->methodName,
                    $this->methodArguments
                ),
                $this->channelResolver,
                $this->isReplyRequired
            )
        );
    }

    public function __toString()
    {
        return "service activator";
    }
}