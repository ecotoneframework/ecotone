<?php
declare(strict_types=1);

namespace Fixture\Service;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ServiceReturningMessage
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceReturningMessage
{
    /**
     * @var Message
     */
    private $messageToReturn;

    /**
     * ServiceReturningMessage constructor.
     * @param Message $messageToReturn
     */
    private function __construct(Message $messageToReturn)
    {
        $this->messageToReturn = $messageToReturn;
    }

    /**
     * @param Message $messageToReturn
     * @return ServiceReturningMessage
     */
    public static function createWith(Message $messageToReturn) : self
    {
        return new self($messageToReturn);
    }

    /**
     */
    public function get()
    {
        return $this->messageToReturn;
    }
}