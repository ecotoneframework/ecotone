<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Filter;

use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class MessageFilter
 * @package SimplyCodedSoftware\Messaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class MessageFilter
{
    /**
     * @var MessageProcessor
     */
    private $messageSelector;
    /**
     * @var null|MessageChannel
     */
    private $discardChannel;
    /**
     * @var bool
     */
    private $throwExceptionOnDiscard;

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