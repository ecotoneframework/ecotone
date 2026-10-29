<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Message;

use function gettype;

use InvalidArgumentException;

use function is_object;
use function is_string;

class BusRoutingKeyResolver
{
    public function __construct(private string $routingKeyHeader)
    {
    }

    public function resolve(Message $message): string
    {
        if ($message->getHeaders()->containsKey($this->routingKeyHeader)) {
            $routingKey = $message->getHeaders()->get($this->routingKeyHeader);
            if (! is_string($routingKey)) {
                throw new InvalidArgumentException(sprintf('Routing key should be a string, but got %s', gettype($routingKey)));
            }
        } else {
            $payload = $message->getPayload();
            if (! is_object($payload)) {
                throw new InvalidArgumentException(sprintf('Routing key should be provided in the message header \''. $this->routingKeyHeader . '\' or the payload should be an object, but got %s', gettype($payload)));
            }
            $routingKey = get_class($payload);
        }

        return $routingKey;
    }
}
