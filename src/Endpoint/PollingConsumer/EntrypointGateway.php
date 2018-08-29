<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EntrypointGateway
{
    /**
     * @param Message $message
     */
    public function execute(Message $message) : void;
}