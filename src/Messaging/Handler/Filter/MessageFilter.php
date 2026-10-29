<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\ResultToMessageConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class MessageFilter
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class MessageFilter implements ResultToMessageConverter
{
    public function __construct(
        private ?MessageChannel $discardChannel,
        private bool            $throwExceptionOnDiscard
    ) {
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        if (! $result) {
            return $requestMessage;
        }

        if ($this->discardChannel) {
            $this->discardChannel->send($requestMessage);
        }

        if ($this->throwExceptionOnDiscard) {
            throw MessageFilterDiscardException::create("Message with id {$requestMessage->getHeaders()->get(MessageHeaders::MESSAGE_ID)} was discarded");
        }

        return null;
    }
}
