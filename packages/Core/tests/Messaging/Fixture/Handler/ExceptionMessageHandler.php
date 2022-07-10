<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class ExceptionMessageHandler
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExceptionMessageHandler implements MessageHandler
{
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        throw new \InvalidArgumentException("testing exception");
    }

    public function __toString()
    {
        return self::class;
    }
}