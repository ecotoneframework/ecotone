<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ServiceReturningMessage
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
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