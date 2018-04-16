<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface GenericPollableGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GenericPollableGateway
{
    /**
     * @param Message $message
     */
    public function runFlow(Message $message) : void;
}