<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;

/**
 * Class QueryBusRouter
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class QueryBusRouter
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
        Assert::isObject($object, 'Passed non object value to Query Bus: ' . TypeDescriptor::createFromVariable($object)->toString() . '. Did you wanted to use convertAndSend?');

        $className = get_class($object);
        if (! array_key_exists($className, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send query to {$className}. No Query Handler defined for it. Have you forgot to add #[QueryHandler] to method?");
        }

        $this->loggingGateway->info(sprintf('Sending Query Message using Class routing: %s.', $className), $message);
        return $this->channelMapping[$className];
    }

    public function routeByName(?string $name, Message $message): array
    {
        if (is_null($name)) {
            throw DestinationResolutionException::create('Lack of routing key for sending via Query Bus');
        }

        if (! array_key_exists($name, $this->channelMapping)) {
            throw DestinationResolutionException::create("Can't send query to {$name}. No Query Handler defined for it. Have you forgot to add #[QueryHandler] to method?");
        }

        $this->loggingGateway->info(sprintf('Sending Query Message using Named routing: %s.', $name), $message);
        return $this->channelMapping[$name];
    }
}
