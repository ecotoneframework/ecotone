<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

use function is_object;

class TypeAliasResolverProcessor implements MessageProcessor
{
    public function __construct(private array $typeAliases, private string $typeHeaderName)
    {
    }

    public function process(Message $message): ?Message
    {
        if ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
            return $message;
        }

        if ($message->getHeaders()->containsKey($this->typeHeaderName)) {
            $routingKey = $message->getHeaders()->get($this->typeHeaderName);
            if (isset($this->typeAliases[$routingKey])) {
                return MessageBuilder::fromMessage($message)
                    ->setHeader(MessageHeaders::TYPE_ID, $routingKey)
                    ->build();
            }
        }

        if (is_object($message->getPayload())) {
            return MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::TYPE_ID, get_class($message->getPayload()))
                ->build();
        } else {
            return $message;
        }
    }
}
