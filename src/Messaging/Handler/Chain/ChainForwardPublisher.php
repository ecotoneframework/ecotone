<?php


namespace SimplyCodedSoftware\Messaging\Handler\Chain;

use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ChainForwardPublisher
 * @package SimplyCodedSoftware\Messaging\Handler\Chain
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