<?php

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * @licence Apache-2.0
 */
class ChainedMessageProcessor implements MessageProcessor
{
    /**
     * @param MessageProcessor[] $messageProcessors
     */
    public function __construct(private array $messageProcessors)
    {
    }

    public function process(Message $message): ?Message
    {
        $resultMessage = $message;
        foreach ($this->messageProcessors as $messageProcessor) {
            $resultMessage = $messageProcessor->process($resultMessage);
            if (! $resultMessage) {
                return null;
            }
        }

        return $resultMessage;
    }
}
