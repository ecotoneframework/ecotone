<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class ExceptionalMessageHandler
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExceptionalMessageHandler implements MessageHandler
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        throw new \RuntimeException("test error, should be caught");
    }

    public function __toString()
    {
        return self::class;
    }
}