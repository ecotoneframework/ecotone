<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Message;

/**
 * Class ReplyMessageProducer
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class FakeReplyMessageProducer implements \Ecotone\Messaging\Handler\MessageProcessor
{
    private $replyData;

    /**
     * ReplyMessageProducer constructor.
     * @param $replyData
     */
    private function __construct($replyData)
    {
        $this->replyData = $replyData;
    }

    public static function create($replyData): self
    {
        return new self($replyData);
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message)
    {
        return $this->replyData;
    }

    public function getMethodCall(Message $message): MethodCall
    {
        return MethodCall::createWith([], false);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return self::class;
    }

    public function getEndpointAnnotations(): array
    {
        return [];
    }

    public function getMethodName(): string
    {
        return 'executeEndpoint';
    }

    public function __toString(): string
    {
        return self::class;
    }
}
