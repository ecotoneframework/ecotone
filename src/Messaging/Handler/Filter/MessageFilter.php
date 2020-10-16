<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class MessageFilter
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class MessageFilter
{
    private \Ecotone\Messaging\Handler\MessageProcessor $messageSelector;
    private ?\Ecotone\Messaging\MessageChannel $discardChannel;
    private bool $throwExceptionOnDiscard;

    /**
     * MessageFilter constructor.
     *
     * @param MessageProcessor    $messageSelector
     * @param null|MessageChannel $discardChannel
     * @param bool                $throwExceptionOnDiscard
     */
    public function __construct(MessageProcessor $messageSelector, ?MessageChannel $discardChannel, bool $throwExceptionOnDiscard)
    {
        $this->messageSelector      = $messageSelector;
        $this->discardChannel = $discardChannel;
        $this->throwExceptionOnDiscard = $throwExceptionOnDiscard;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): ?Message
    {
        if ($this->messageSelector->processMessage($message)) {
            return $message;
        }

        if ($this->discardChannel) {
            $this->discardChannel->send($message);
        }

        if ($this->throwExceptionOnDiscard) {
            throw MessageFilterDiscardException::create("Message with id {$message->getHeaders()->get(MessageHeaders::MESSAGE_ID)} was discarded");
        }

        return null;
    }
}