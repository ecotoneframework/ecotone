<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Throwable;

/**
 * Class ThrowExceptionMessageProcessor
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ThrowExceptionMessageProcessor implements MessageProcessor
{
    /**
     * @var Throwable
     */
    private $exception;

    private function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public static function create(Throwable $exception): self
    {
        return new self($exception);
    }

    /**
     * @inheritDoc
     */
    public function process(Message $message): ?Message
    {
        throw $this->exception;
    }
}
