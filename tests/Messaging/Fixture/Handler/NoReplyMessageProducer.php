<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * Class NoReplyMessageProducer
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NoReplyMessageProducer implements MessageProcessor
{
    private $wasCalled = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function process(Message $message): ?Message
    {
        $this->wasCalled = true;
        return null;
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}
