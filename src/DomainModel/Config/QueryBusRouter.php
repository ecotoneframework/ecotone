<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use SimplyCodedSoftware\DomainModel\QueryBus;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class QueryBusRouter
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueryBusRouter
{
    /**
     * @var array
     */
    private $classNameToChannelNameMapping;
    /**
     * @var array
     */
    private $availableCommandChannels;
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
        foreach ($classNameToChannelNameMapping as $typeName => $channelNames) {
            foreach ($channelNames as $channelName) {
                $this->availableCommandChannels[$channelName] = $typeName;
            }
        }
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param object $object
     *
     * @return string|null
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByObject($object) : array
    {
        Assert::isObject($object, "Passed non object value to Query Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->classNameToChannelNameMapping)) {
            throw DestinationResolutionException::create("Can't send query to {$className}. No Query Handler defined for it. Have you forgot to add @QueryHandler to method or @MessageEndpoint to class?");
        }

        return $this->classNameToChannelNameMapping[$className];
    }

    /**
     * @param string $name
     *
     * @return string
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByName(?string $name) : string
    {
        if (is_null($name)) {
            throw ConfigurationException::create("Can't send via name using QueryBus without " . QueryBus::CHANNEL_NAME_BY_NAME . " header defined");
        }

        if (!array_key_exists($name, $this->availableCommandChannels)) {
            throw ConfigurationException::create("Can't send query to {$name}. No Query Handler defined for it. Have you forgot to add @QueryHandler to method or @MessageEndpoint to class?");
        }

        return $name;
    }
}