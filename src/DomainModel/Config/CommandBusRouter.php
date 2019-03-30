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
    private $classNameToChannelNameMapping;
    /**
     * @var array
     */
    private $channelNameToClassNameMapping;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * CommandBusRouter constructor.
     *
     * @param array           $classNameToChannelNameMapping
     * @param ChannelResolver $channelResolver
     */
    public function __construct(array $classNameToChannelNameMapping, ChannelResolver $channelResolver)
    {
        $this->classNameToChannelNameMapping = $classNameToChannelNameMapping;
        foreach ($classNameToChannelNameMapping as $className => $channelNames) {
            $this->channelNameToClassNameMapping[$channelNames[0]] = $className;
        }
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
        if (!array_key_exists($className, $this->classNameToChannelNameMapping)) {
            throw ConfigurationException::create("Can't send command to {$className}. No Command Handler defined for it. Have you forgot to add @CommandHandler to method or @MessageEndpoint to class?");
        }

        return $this->classNameToChannelNameMapping[$className];
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

        if (!array_key_exists($name, $this->channelNameToClassNameMapping)) {
            throw ConfigurationException::create("Can't send command to {$name}. No Command Handler defined for it. Have you forgot to add @CommandHandler to method or @MessageEndpoint to class?");
        }

        return $name;
    }
}