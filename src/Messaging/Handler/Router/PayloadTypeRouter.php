<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class PayloadTypeRouter
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class PayloadTypeRouter
{
    /**
     * @var array
     */
    private $typeToChannelMapping;
    /**
     * @var bool
     */
    private $routeByClassName;

    /**
     * PayloadTypeRouter constructor.
     *
     * @param array $typeToChannelMapping
     * @param bool  $routeByClassName
     */
    private function __construct(array $typeToChannelMapping, bool $routeByClassName)
    {
        $this->typeToChannelMapping = $typeToChannelMapping;
        $this->routeByClassName = $routeByClassName;
    }

    /**
     * @param array $typeToChannelMapping
     * @return PayloadTypeRouter
     */
    public static function create(array $typeToChannelMapping) : self
    {
        return new self($typeToChannelMapping, false);
    }

    /**
     * @return PayloadTypeRouter
     */
    public static function createWithRoutingByClass() : self
    {
        return new self([], true);
    }

    /**
     * @param Message $message
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function route(Message $message) : array
    {
        if (!is_object($message->getPayload())) {
            throw new InvalidArgumentException("Can't route by payload type, when payload is not object. Got: {$message->getPayload()}");
        }

        $classToRoute = get_class($message->getPayload());
        if ($this->routeByClassName) {
            return [$classToRoute];
        }

        $channelsToRoute = [];
        foreach ($this->typeToChannelMapping as $type => $channelName) {
            if ($classToRoute === $type) {
                $channelsToRoute[] = $channelName;
            }
        }

        return $channelsToRoute;
    }
}