<?php

declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    protected $inputMessageChannelName;
    /**
     * @var string
     */
    protected $outputMessageChannelName;
    /**
     * @var string
     */
    protected $messageHandlerName;
    /**
     * @var ChannelResolver
     */
    protected $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    protected $referenceSearchService;

    /**
     * @param string $name
     * @return self|static
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
     * @param string $messageChannelName
     * @return self|static
     */
    public function withInputMessageChannel(string $messageChannelName) : self
    {
        $this->inputMessageChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @param string $messageChannelName
     * @return self|static
     */
    public function withOutputMessageChannel(string $messageChannelName) : self
    {
        $this->outputMessageChannelName = $messageChannelName;

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
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService): MessageHandlerBuilder
    {
        $this->referenceSearchService = $referenceSearchService;

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
}