<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Filter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class MessageFilterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var MessageToParameterConverterBuilder[]
     */
    private $parameterConverters = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string
     */
    private $inputChannelName;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $outputChannelName;
    /**
     * @var string
     */
    private $discardChannelName;
    /**
     * @var bool
     */
    private $throwExceptionOnDiscard = false;

    /**
     * MessageFilterBuilder constructor.
     *
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(string $inputChannelName, string $outputChannelName, string $referenceName, string $methodName)
    {
        $this->inputChannelName  = $inputChannelName;
        $this->referenceName     = $referenceName;
        $this->methodName        = $methodName;
        $this->outputChannelName = $outputChannelName;
    }

    /**
     * @param string $inputChannelName
     * @param string $outputChannelName
     * @param string $referenceName
     * @param string $methodName
     *
     * @return MessageFilterBuilder
     */
    public static function createWithReferenceName(string $inputChannelName, string $outputChannelName, string $referenceName, string $methodName): self
    {
        return new self($inputChannelName, $outputChannelName, $referenceName, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferences[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        $this->parameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @param string $discardChannelName
     *
     * @return MessageFilterBuilder
     */
    public function withDiscardChannelName(string $discardChannelName): self
    {
        $this->discardChannelName = $discardChannelName;

        return $this;
    }

    /**
     * @param bool $throwOnDiscard
     *
     * @return MessageFilterBuilder
     */
    public function withThrowingExceptionOnDiscard(bool $throwOnDiscard): self
    {
        $this->throwExceptionOnDiscard = $throwOnDiscard;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $parameterConverters = [];
        foreach ($this->parameterConverters as $parameterConverter) {
            $parameterConverters[] = $parameterConverter->build($referenceSearchService);
        }
        $messageSelector = $referenceSearchService->findByReference($this->referenceName);

        if (!InterfaceToCall::createFromObject($messageSelector, $this->methodName)->hasReturnValueBoolean()) {
            throw InvalidArgumentException::create("Object with reference {$this->referenceName} should return bool for method {$this->methodName} while using Message Filter");
        }

        $discardChannel = $this->discardChannelName ? $channelResolver->resolve($this->discardChannelName) : null;

        $serviceActivatorBuilder = ServiceActivatorBuilder::createWithDirectReference(
            $this->inputChannelName,
            new MessageFilter(MethodInvoker::createWith($messageSelector, $this->methodName, $parameterConverters), $discardChannel, $this->throwExceptionOnDiscard),
            "handle"
        )->withOutputMessageChannel($this->outputChannelName);

        return $serviceActivatorBuilder->build($channelResolver, $referenceSearchService);
    }
}