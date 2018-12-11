<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EntrypointGateway
{
    /**
     * @param Message $message
     */
    public function execute(Message $message) : void;
}