<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ChainForwarder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainForwarder
{
    /**
     * @var ChainGateway
     */
    private $chainGateway;

    /**
     * ChainForwarder constructor.
     * @param ChainGateway $chainGateway
     */
    public function __construct(ChainGateway $chainGateway)
    {
        $this->chainGateway = $chainGateway;
    }

    /**
     * @param Message $message
     * @return null|Message
     */
    public function forward(Message $message) : ?Message
    {
//        $replyChannel = $message->getHeaders()->getReplyChannel();
//        $messageWithLocalReturn = MessageBuilder::fromMessage($message)
//                                    ->removeHeader(MessageHeaders::REPLY_CHANNEL)
//                                    ->build();
//
        return $this->chainGateway->execute($message);
    }
}