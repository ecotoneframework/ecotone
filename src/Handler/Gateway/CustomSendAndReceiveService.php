<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class CustomSendAndReceiveService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CustomSendAndReceiveService extends SendAndReceiveService
{
    /**
     * @param DirectChannel $requestChannel
     * @param null|PollableChannel $replyChannel
     * @param null|MessageChannel $errorChannel
     */
    public function setSendAndReceive(DirectChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel) : void;
}