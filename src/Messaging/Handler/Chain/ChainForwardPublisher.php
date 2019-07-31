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
    /**
     * @var MessageChannel
     */
    private $requestChannel;

    /**
     * ChainForwardPublisher constructor.
     * @param MessageChannel $requestChannel
     */
    public function __construct(MessageChannel $requestChannel)
    {
        $this->requestChannel = $requestChannel;
    }

    /**
     * @param Message $requestMessage
     * @return Message|null
     */
    public function forward(Message $requestMessage): ?Message
    {
        $replyChannelComingFromCurrentMessage = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;

        $replyChannel = QueueChannel::create();
        $requestMessageWithNewChainReply = MessageBuilder::fromMessage($requestMessage)
                ->setReplyChannel($replyChannel)
                ->build();

        $this->requestChannel->send($requestMessageWithNewChainReply);

        $replyMessage = $replyChannel->receive();

        if ($replyMessage && $replyChannelComingFromCurrentMessage) {
            $replyMessage = MessageBuilder::fromMessage($replyMessage)
                                ->setReplyChannel($replyChannelComingFromCurrentMessage)
                                ->build();
        }

        return $replyMessage;
    }
}