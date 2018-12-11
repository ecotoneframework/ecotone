<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Chain;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface ChainGateway
 * @package SimplyCodedSoftware\Messaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChainGateway
{
    /**
     * @param Message $message
     * @return Message|null
     */
    public function execute(Message $message) : ?Message;
}