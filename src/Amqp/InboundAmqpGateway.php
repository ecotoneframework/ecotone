<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer;

/**
 * Interface EnqueueGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InboundAmqpGateway
{
    /**
     * @param AmqpMessage $amqpMessage
     * @param Consumer $consumer
     * @return mixed
     */
    public function execute(AmqpMessage $amqpMessage, Consumer $consumer);
}