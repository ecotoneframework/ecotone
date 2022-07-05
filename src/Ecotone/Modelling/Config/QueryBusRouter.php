<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use Ecotone\Modelling\QueryBus;

/**
 * Class QueryBusRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueryBusRouter
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
        Assert::isObject($object, "Passed non object value to Query Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send query to {$className}. No Query Handler defined for it. Have you forgot to add #[QueryHandler] to method?");
        }

        return $this->channelMapping[$className];
    }

    public function routeByName(?string $name) : array
    {
        if (is_null($name)) {
            throw DestinationResolutionException::create("Lack of routing key for sending via Query Bus");
        }

        if (!array_key_exists($name, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send query to {$name}. No Query Handler defined for it. Have you forgot to add #[QueryHandler] to method?");
        }

        return $this->channelMapping[$name];
    }
}