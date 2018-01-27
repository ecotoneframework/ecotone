<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class AmqpOutboundChannelAdapterBuilder
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapterBuilder implements MessageHandlerBuilder
{
    /**
     * @var RabbitTemplate
     */
    private $rabbitTemplate;
    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param RabbitTemplate $rabbitTemplate
     * @return AmqpOutboundChannelAdapterBuilder
     */
    public function setRabbitTemplate(RabbitTemplate $rabbitTemplate) : self
    {
        $this->rabbitTemplate = $rabbitTemplate;
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageHandler
    {
        // TODO: Implement build() method.
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        // TODO: Implement getConsumerName() method.
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        // TODO: Implement getInputMessageChannelName() method.
    }

    /**
     * @inheritDoc
     */
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService): MessageHandlerBuilder
    {
        // TODO: Implement setReferenceSearchService() method.
    }

    /**
     * @inheritDoc
     */
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        // TODO: Implement setChannelResolver() method.
    }
}