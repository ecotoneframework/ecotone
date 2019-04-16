<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class EventPublisherRouter
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventBusRouter
{
    /**
     * @var array
     */
    private $classNameToChannelNameMapping;
    /**
     * @var array
     */
    private $availableChannelNames;
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
        foreach ($classNameToChannelNameMapping as $className => $channelNames) {
            foreach ($channelNames as $channelName) {
                $this->availableChannelNames[$channelName] = $className;
            }
        }
        $this->classNameToChannelNameMapping = array_map(function(array $channels) {
            return array_unique($channels);
        }, $classNameToChannelNameMapping);

        $this->channelResolver = $channelResolver;
    }

    /**
     * @param object $object
     *
     * @return string|null
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByObject($object) : array
    {
        Assert::isObject($object, "Passed non object value to Event Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->classNameToChannelNameMapping)) {
            return [];
        }

        return $this->classNameToChannelNameMapping[$className];
    }

    /**
     * @param string $name
     *
     * @return string|null
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByName(?string $name) : ?string
    {
        if (is_null($name)) {
            throw ConfigurationException::create("Can't send via name using EventBus without " . EventBus::CHANNEL_NAME_BY_NAME . " header defined");
        }

        if (!array_key_exists($name, $this->availableChannelNames)) {
            return null;
        }

        return $name;
    }
}