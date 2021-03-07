<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;

/**
 * Class CommandBusRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CommandBusRouter
{
    private array $channelMapping = [];

    /**
     * CommandBusRouter constructor.
     *
     * @param array           $channelMapping
     */
    public function __construct(array $channelMapping)
    {
        $this->channelMapping = $channelMapping;
    }

    public function routeByObject(object $object) : array
    {
        Assert::isObject($object, "Passed non object value to Commmand Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send command to {$className}. No Command Handler defined for it. Have you forgot to add #[CommandHandler] to method?");
        }

        return $this->channelMapping[$className];
    }

    public function routeByName(?string $name) : array
    {
        if (is_null($name)) {
            throw DestinationResolutionException::create("Missing routing key for sending via Command Bus");
        }

        if (!array_key_exists($name, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send command to {$name}. No Command Handler defined for it. Have you forgot to add #[CommandHandler] to method?");
        }

        return $this->channelMapping[$name];
    }
}