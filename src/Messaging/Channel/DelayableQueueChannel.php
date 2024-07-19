<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class DelayableQueueChannel implements PollableChannel, DefinedObject
{
    /**
     * @param Message[] $queue
     */
    private function __construct(private string $name, private array $queue, private int $releaseMessagesAwaitingFor = 0)
    {
    }

    public static function create(string $name = 'unknown'): self
    {
        return new self($name, []);
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->queue[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        $message = array_shift($this->queue);

        if ($message !== null && $message->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY)) {
            if ($message->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) > $this->releaseMessagesAwaitingFor) {
                $nextAvailableMessage = $this->receive();
                array_unshift($this->queue, $message);

                if ($nextAvailableMessage === null) {
                    return null;
                }

                $message = $nextAvailableMessage;
            }

            return MessageBuilder::fromMessage($message)
                ->removeHeader(MessageHeaders::DELIVERY_DELAY)
                ->build();
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->receive();
    }

    public function releaseMessagesAwaitingFor(int $milliseconds): void
    {
        $this->releaseMessagesAwaitingFor = $milliseconds;
    }

    public function __toString()
    {
        return 'in memory delayable: ' . $this->name;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->name], 'create');
    }
}
