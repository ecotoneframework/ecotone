<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Class CustomSendAndReceiveService
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CustomSendAndReceiveService extends SendAndReceiveService
{
    /**
     * @param SubscribableChannel $requestChannel
     * @param null|PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    public function setSendAndReceive(SubscribableChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel) : void;
}