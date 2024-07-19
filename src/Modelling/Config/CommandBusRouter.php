<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;

/**
 * Class CommandBusRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class CommandBusRouter
{
    /**
     * CommandBusRouter constructor.
     *
     * @param array           $channelMapping
     */
    public function __construct(
        private array $channelMapping,
        private LoggingGateway $loggingGateway
    ) {
    }

    public function routeByObject(object $object, Message $message): array
    {
        Assert::isObject($object, 'Passed non object value to Commmand Bus: ' . TypeDescriptor::createFromVariable($object)->toString() . '. Did you wanted to use convertAndSend?');

        $className = get_class($object);
        if (! array_key_exists($className, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send command to {$className}. No Command Handler defined for it. Have you forgot to add #[CommandHandler] to method?");
        }

        $this->loggingGateway->info(sprintf('Sending Command Message using Class routing: %s.', $className), $message);
        return $this->channelMapping[$className];
    }

    public function routeByName(?string $name, Message $message): array
    {
        if (is_null($name)) {
            throw DestinationResolutionException::create('Missing routing key for sending via Command Bus');
        }

        if (! array_key_exists($name, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send command to {$name}. No Command Handler defined for it. Have you forgot to add #[CommandHandler] to method?");
        }

        $this->loggingGateway->info(sprintf('Sending Command Message using Named routing: %s.', $name), $message);
        return $this->channelMapping[$name];
    }
}
