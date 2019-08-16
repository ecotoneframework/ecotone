<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\EventBus;

/**
 * Class EventPublisherRouter
 * @package Ecotone\Modelling\Config
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
    private $availableChannelNames = [];

    /**
     * CommandBusRouter constructor.
     *
     * @param array           $classNameToChannelNameMapping
     */
    public function __construct(array $classNameToChannelNameMapping)
    {
        foreach ($classNameToChannelNameMapping as $className => $channelNames) {
            foreach ($channelNames as $channelName) {
                $this->availableChannelNames[$channelName] = $className;
            }
        }
        $this->classNameToChannelNameMapping = array_map(function(array $channels) {
            return array_unique($channels);
        }, $classNameToChannelNameMapping);
    }

    /**
     * @param object $object
     *
     * @return array
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function routeByObject($object) : array
    {
        Assert::isObject($object, "Passed non object value to Event Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $resolvedChannels = [];
        $reflectionClass = new \ReflectionClass($object);
        $parent = $reflectionClass;
        while ($parent = $parent->getParentClass()) {
            $resolvedChannels = array_merge($resolvedChannels, $this->getChannelsForClassName($parent));
        }

        return array_values(array_unique(array_merge($resolvedChannels, $this->getChannelsForClassName($reflectionClass))));
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return array
     * @throws \ReflectionException
     */
    private function getChannelsForClassName(\ReflectionClass $class) : array
    {
        $channelNames = [];
        foreach ($class->getInterfaceNames() as $interfaceName) {
            $channelNames = array_merge($channelNames, $this->getChannelsForClassName(new \ReflectionClass($interfaceName)));
        }

        $className = $class->getName();
        if (array_key_exists($className, $this->classNameToChannelNameMapping)) {
            $channelNames =  array_merge($channelNames, $this->classNameToChannelNameMapping[$className]);
        }

        return $channelNames;
    }

    /**
     * @param string $name
     *
     * @return string|null
     * @throws \Ecotone\Messaging\MessagingException
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