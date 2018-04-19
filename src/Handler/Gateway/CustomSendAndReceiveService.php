<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;

/**
 * Class CustomSendAndReceiveService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
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