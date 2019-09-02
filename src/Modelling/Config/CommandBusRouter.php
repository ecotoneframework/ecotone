<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\CommandBus;

/**
 * Class CommandBusRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CommandBusRouter
{
    /**
     * @var array
     */
    private $classNameToChannelNameMapping = [];
    /**
     * @var array
     */
    private $availableChannelNames = [];
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
            foreach ($channelNames as $channelName) {
                $this->availableChannelNames[$channelName] = $className;
            }
        }
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param object $object
     *
     * @return array
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
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
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function routeByName(?string $name) : string
    {
        if (is_null($name)) {
            throw ConfigurationException::create("Can't send via name using CommandBus without " . CommandBus::CHANNEL_NAME_BY_NAME . " header defined");
        }

        if (!array_key_exists($name, $this->availableChannelNames)) {
            throw ConfigurationException::create("Can't send command to {$name}. No Command Handler defined for it. Have you forgot to add @CommandHandler to method or @MessageEndpoint to class?");
        }

        return $name;
    }
}