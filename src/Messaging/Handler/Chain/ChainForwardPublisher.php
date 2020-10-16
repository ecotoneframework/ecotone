<?php


namespace Ecotone\Messaging\Handler\Chain;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class ChainForwardPublisher
 * @package Ecotone\Messaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainForwardPublisher
{
    private \Ecotone\Messaging\MessageChannel $requestChannel;
    private bool $hasOutputChannel;

    public function __construct(MessageChannel $requestChannel, bool $hasOutputChannel)
    {
        $this->requestChannel = $requestChannel;
        $this->hasOutputChannel = $hasOutputChannel;
    }

    /**
     * Is responsible for forwarding message into the chain and receiving message from it
     * after that pushes the the message to the output channel, which can be next chain
     */
    public function forward(Message $requestMessage): ?Message
    {
        $replyChannelComingFromCurrentMessage = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;

        $replyChannel = null;
        $requestMessageWithNewChainReply = MessageBuilder::fromMessage($requestMessage);
        if ($replyChannelComingFromCurrentMessage || $this->hasOutputChannel) {
            $replyChannel = QueueChannel::create();
            $requestMessageWithNewChainReply->setReplyChannel($replyChannel);
        }
        $requestMessageWithNewChainReply = $requestMessageWithNewChainReply->build();

        $this->requestChannel->send($requestMessageWithNewChainReply);

        if (!$replyChannel) {
            return null;
        }

        $replyMessage = $replyChannel->receive();

        if ($replyMessage && $replyChannelComingFromCurrentMessage) {
            $replyMessage = MessageBuilder::fromMessage($replyMessage)
                                ->setReplyChannel($replyChannelComingFromCurrentMessage)
                                ->build();
        }

        return $replyMessage;
    }
}