<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Channel\AbstractChannelInterceptor;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class PrepareSendMessageTransformer
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PrepareReplyChannelsInterceptor extends AbstractChannelInterceptor
{
    /**
     * @var PollableChannel|null
     */
    private $replyChannel;
    /**
     * @var MessageChannel|null
     */
    private $errorChannel;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;

    /**
     * ReceivePoller constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    public function __construct(InterfaceToCall $interfaceToCall, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel)
    {
        $this->replyChannel = $replyChannel;
        $this->errorChannel = $errorChannel;
        $this->interfaceToCall = $interfaceToCall;
    }

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        if (!$this->interfaceToCall->hasReturnValue()) {
            return $message;
        }

        $messageBuilder = MessageBuilder::fromMessage($message)
            ->setErrorChannel($this->errorChannel ? $this->errorChannel : $this->replyChannel)
            ->setReplyChannel($this->replyChannel);

        if (!$this->replyChannel) {
            return $messageBuilder
                ->build();
        }

        return $messageBuilder->build();
    }
}