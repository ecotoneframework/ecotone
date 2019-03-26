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
     * @return string|null
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function routeByObject($object) : array
    {
        Assert::isObject($object, "Passed non object value to Query Bus: " . TypeDescriptor::createFromVariable($object)->toString() . ". Did you wanted to use convertAndSend?");

        $className = get_class($object);
        if (!array_key_exists($className, $this->channelNamesRouting)) {
            throw DestinationResolutionException::create("There is no Query Handler defined for {$className}. Have you forgot to add @QueryHandler annotation?");
        }

        return $this->channelNamesRouting[$className];
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

        if (!$this->channelResolver->hasChannelWithName($name)) {
            throw ConfigurationException::create("Can't send command to {$name}. No Query Handler defined with this name. Have you forgot to add @QueryHandler annotation?");
        }

        return $name;
    }
}