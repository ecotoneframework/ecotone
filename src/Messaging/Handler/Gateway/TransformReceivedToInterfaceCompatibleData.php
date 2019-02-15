<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Channel\AbstractChannelInterceptor;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Class TransformReceivedToInterfaceCompatibleData
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformReceivedToInterfaceCompatibleData extends AbstractChannelInterceptor
{
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;

    /**
     * TransformReceivedToInterfaceCompatibleData constructor.
     * @param InterfaceToCall $interfaceToCall
     */
    public function __construct(InterfaceToCall $interfaceToCall)
    {
        $this->interfaceToCall = $interfaceToCall;
    }

    /**
     * @inheritDoc
     */
    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
//        if ($this->interfaceToCall->doesItReturnMessage() && $replyMessage) {
//            $replyMessageBuilder = MessageBuilder::fromMessage($replyMessage);
//            if ($replyChannel) {
//                $replyMessageBuilder->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannel);
//            }
//            if ($errorChannel) {
//                $replyMessageBuilder->setHeader(MessageHeaders::ERROR_CHANNEL, $errorChannel);
//            }
//
//            return $replyMessageBuilder->build();
//        }
//
//        $reply = null;
//        if ($replyMessage) {
//            foreach ($this->messageConverters as $messageConverter) {
//                $reply = $messageConverter->fromMessage(
//                    $replyMessage,
//                    $this->interfaceToCall->getReturnType()
//                );
//
//                if ($reply) {
//                    break;
//                }
//            }
//
//            if (!$reply) {
//                $reply = $replyMessage ? $replyMessage->getPayload() : null;
//            }
//        }
    }
}