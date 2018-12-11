<?php

namespace SimplyCodedSoftware\Messaging\Handler\Router;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class RecipientListRouter
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class RecipientListRouter
{
    /**
     * @var string[]
     */
    private $recipientMessageChannels;

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