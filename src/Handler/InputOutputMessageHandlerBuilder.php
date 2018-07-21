<?php

declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Class InputOutputMessageHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    protected $inputMessageChannelName = "";
    /**
     * @var string
     */
    protected $outputMessageChannelName = "";
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    protected $preCallInterceptors = [];
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    protected $postCallInterceptors = [];

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
     * @inheritDoc
     */
    public function withOutputMessageChannel(string $messageChannelName) : self
    {
        $this->outputMessageChannelName = $messageChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $this->inputMessageChannelName = $inputChannelName;

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
    public function withPreCallInterceptors(array $preCallInterceptors)
    {
        $this->preCallInterceptors = $preCallInterceptors;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withPostCallInterceptors(array $postCallInterceptors)
    {
        $this->postCallInterceptors = $postCallInterceptors;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPreCallInterceptors(): array
    {
        return $this->preCallInterceptors;
    }

    /**
     * @inheritDoc
     */
    public function getPostCallInterceptors(): array
    {
        return $this->postCallInterceptors;
    }
}