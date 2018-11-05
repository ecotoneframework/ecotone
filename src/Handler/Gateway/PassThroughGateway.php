<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface PassThroughGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PassThroughGateway
{
    /**
     * @param Message $message
     * @return Message
     */
    public function execute(Message $message) : Message;
}