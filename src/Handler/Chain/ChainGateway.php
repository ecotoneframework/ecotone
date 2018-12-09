<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface ChainGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Chain
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