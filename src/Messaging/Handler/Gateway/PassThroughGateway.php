<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface PassThroughGateway
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
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