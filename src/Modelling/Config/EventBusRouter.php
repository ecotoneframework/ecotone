<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use ReflectionClass;
use ReflectionException;

/**
 * Class EventPublisherRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EventBusRouter
{
    public function __construct(
        private array $channelMapping,
        private LoggingGateway $loggingGateway
    ) {

    }

    public function routeByObject(object $object, Message $message): array
    {
        $resolvedChannels = [];
        $reflectionClass = new ReflectionClass($object);
        $parent = $reflectionClass;
        if (array_key_exists(TypeDescriptor::OBJECT, $this->channelMapping)) {
            $resolvedChannels =  array_merge($resolvedChannels, $this->channelMapping[TypeDescriptor::OBJECT]);
        }
        while ($parent = $parent->getParentClass()) {
            $resolvedChannels = array_merge($resolvedChannels, $this->getChannelsForClassName($parent));
        }
        $channelsToSend = array_values(array_unique(array_merge($resolvedChannels, $this->getChannelsForClassName($reflectionClass))));

        $this->loggingGateway->info(
            sprintf('Publishing Event Message using Class routing: %s.', $reflectionClass->getName()),
            $message,
            contextData: ['resolvedChannels' => $channelsToSend]
        );
        return $channelsToSend;
    }

    /**
     * @param ReflectionClass $class
     *
     * @return array
     * @throws ReflectionException
     */
    private function getChannelsForClassName(ReflectionClass $class): array
    {
        $channelNames = [];
        foreach ($class->getInterfaceNames() as $interfaceName) {
            $channelNames = array_merge($channelNames, $this->getChannelsForClassName(new ReflectionClass($interfaceName)));
        }

        $className = $class->getName();
        if (array_key_exists($className, $this->channelMapping)) {
            $channelNames =  array_merge($channelNames, $this->channelMapping[$className]);
        }

        return $channelNames;
    }

    /**
     * @param string|null $routedName
     *
     * @return array
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function routeByName(?string $routedName, Message $message): array
    {
        if (is_null($routedName)) {
            throw ConfigurationException::create('Lack of routing key for sending via EventBus');
        }

        $resolvedChannels = [];
        foreach ($this->channelMapping as $listenFor => $destinationChannels) {
            if (self::doesListenForRoutedName($listenFor, $routedName)) {
                $resolvedChannels = array_merge($resolvedChannels, $destinationChannels);
            }
        }
        $resolvedChannels = array_unique($resolvedChannels);

        $this->loggingGateway->info(
            sprintf('Publishing Event Message using Named routing: %s.', $routedName),
            $message,
            contextData: ['resolvedChannels' => $resolvedChannels]
        );
        return $resolvedChannels;
    }

    public static function isRegexBasedRoute(string $channelName): bool
    {
        return preg_match("#\*#", $channelName);
    }

    public static function doesListenForRoutedName(string $listenFor, string $routedName): bool
    {
        $listenFor = str_replace('\\', '\\\\', $listenFor);
        if (preg_match('#^' . str_replace('*', '.*', $listenFor) . '$#', $routedName)) {
            return true;
        }

        return false;
    }
}
