<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Router;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class RecipientListRouter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Router
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