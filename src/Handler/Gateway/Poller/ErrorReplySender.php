<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Poller;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ReplySender;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\ErrorMessage;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ErrorReplySender
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ErrorReplySender implements ReplySender
{
    /**
     * @var ReplySender
     */
    private $replySender;

    /**
     * ErrorReplySender constructor.
     * @param ReplySender $replySender
     */
    private function __construct(ReplySender $replySender)
    {
        $this->replySender = $replySender;
    }

    /**
     * @param ReplySender $replySender
     * @return ErrorReplySender
     */
    public static function create(ReplySender $replySender) : self
    {
        return new self($replySender);
    }

    /**
     * @inheritDoc
     */
    public function prepareFor(InterfaceToCall $interfaceToCall, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $this->replySender->prepareFor($interfaceToCall, $messageBuilder);
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        $replyMessage = $this->replySender->receiveReply();

        if (is_null($replyMessage)) {
            return null;
        }
        if ($replyMessage instanceof ErrorMessage) {
            throw MessageHandlingException::fromErrorMessage($replyMessage);
        }

        return $replyMessage;
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return $this->replySender->hasReply();
    }
}