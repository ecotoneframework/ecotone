<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;

/**
 * Class ServiceReturningMessage
 * @package Test\Ecotone\Messaging\Fixture\Service
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

    public static function createWith(Message $messageToReturn): self
    {
        return new self($messageToReturn);
    }

    #[ServiceActivator('get')]
    public function get()
    {
        return $this->messageToReturn;
    }
}
