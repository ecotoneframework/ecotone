<?php

namespace Ecotone\Messaging\Handler\Router;
use Ecotone\Messaging\Message;

/**
 * Class RecipientListRouter
 * @package Ecotone\Messaging\Handler\Router
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class RecipientListRouter
{
    /**
     * @var string[]
     */
    private array $recipientMessageChannels;

    /**
     * RecipientListRouter constructor.
     *
     * @param string[] $recipientMessageChannels
     */
    public function __construct(array $recipientMessageChannels)
    {
        $this->recipientMessageChannels = $recipientMessageChannels;
    }

    /**
     * @inheritDoc
     */
    public function route(Message $message): array
    {
        return $this->recipientMessageChannels;
    }
}