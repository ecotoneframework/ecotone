<?php

namespace Messaging\Handler\Router;

use Messaging\Message;

/**
 * Class PayloadTypeRouter
 * @package Messaging\Handler\Router
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
     * PayloadTypeRouter constructor.
     * @param array $typeToChannelMapping
     */
    private function __construct(array $typeToChannelMapping)
    {
        $this->typeToChannelMapping = $typeToChannelMapping;
    }

    /**
     * @param array $typeToChannelMapping
     * @return PayloadTypeRouter
     */
    public static function create(array $typeToChannelMapping) : self
    {
        return new self($typeToChannelMapping);
    }

    /**
     * @param Message $message
     * @return array
     */
    public function route(Message $message) : array
    {
        $channelsToRoute = [];
        foreach ($this->typeToChannelMapping as $type => $channelName) {
            if (get_class($message->getPayload()) === $type) {
                $channelsToRoute[] = $channelName;
            }
        }

        return $channelsToRoute;
    }
}