<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\DomainModel\CommandBus;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class CommandBusRouter
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CommandBusRouter
{
    /**
     * @var array
     */
    private $channelNamesRouting;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * CommandBusRouter constructor.
     *
     * @param array           $channelNamesRouting
     * @param ChannelResolver $channelResolver
     */
    public function __construct(array $channelNamesRouting, ChannelResolver $channelResolver)
    {
        $this->channelNamesRouting = $channelNamesRouting;
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param object $object
     *
     * @return array
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByObject($object) : array
    {
        Assert::isObject($object, "Passed non object value to Commmand Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->channelNamesRouting)) {
            throw DestinationResolutionException::create("There is no Command Handler defined for {$className}. Have you forgot to add @CommandHandler annotation?");
        }

        return $this->channelNamesRouting[$className];
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByName(?string $name) : string
    {
        if (is_null($name)) {
            throw ConfigurationException::create("Can't send via name using CommandBus without " . CommandBus::CHANNEL_NAME_BY_NAME . " header defined");
        }

        if (!$this->channelResolver->hasChannelWithName($name)) {
            throw ConfigurationException::create("Can't send command to {$name}. No Command Handler defined with this name. Have you forgot to add @CommandHandler annotation?");
        }

        return $name;
    }
}