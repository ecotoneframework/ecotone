<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class ReplyMessageInterceptor
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReplyMessageInterceptor
{
    /**
     * @var PollableChannel|null
     */
    private $replyChannel;
    /**
     * @var MessageChannel|null
     */
    private $errorChannel;

//    public function __construct(?PollableChannel $replyChannel, ?MessageChannel $errorChannel)
//    {
//        $this->replyChannel = $replyChannel;
//        $this->errorChannel = $errorChannel;
//    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $requestMessage
     * @return Message
     */
    public function buildReply(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        $replyChannelComingFromPreviousGateway = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;
        $errorChannelComingFromPreviousGateway = $requestMessage->getHeaders()->containsKey(MessageHeaders::ERROR_CHANNEL) ? $requestMessage->getHeaders()->getErrorChannel() : null;

        $reply = $methodInvocation->proceed();

        if (!is_null($reply)) {
            $replyMessageBuilder = MessageBuilder::withPayload($reply);
            if ($replyChannelComingFromPreviousGateway) {
                $replyMessageBuilder->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannelComingFromPreviousGateway);
            }
            if ($errorChannelComingFromPreviousGateway) {
                $replyMessageBuilder->setHeader(MessageHeaders::ERROR_CHANNEL, $errorChannelComingFromPreviousGateway);
            }

            return $replyMessageBuilder->build();
        }

        return null;
    }
}