<?php

namespace SimplyCodedSoftware\DomainModel\Config;
use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class CqrsRouting
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageToChannelRouter
{
    /**
     * @var array|string[]
     */
    private $classChannelNameMapping;

    /**
     * CqrsRouter constructor.
     * @param string[] $classChannelNameMapping
     */
    public function __construct(array $classChannelNameMapping)
    {
        $this->classChannelNameMapping = $classChannelNameMapping;
    }

    /**
     * @param Message $message
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function route(Message $message) : string
    {
        $routingKey = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_MESSAGE_CHANNEL_NAME_TO_SEND)
            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_MESSAGE_CHANNEL_NAME_TO_SEND)
            : TypeDescriptor::createFromVariable($message->getPayload())->toString();

        if (!array_key_exists($routingKey, $this->classChannelNameMapping)) {
            throw new InvalidArgumentException("Can't find correct channel mapping for object {$routingKey}. Are you sure you have configured aggregate correctly?");
        }

        return $this->classChannelNameMapping[$routingKey];
    }
}