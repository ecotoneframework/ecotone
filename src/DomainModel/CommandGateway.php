<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;

/**
 * Interface CommandGateway
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface CommandGateway
{
    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=AggregateMessage::AGGREGATE_SEND_MESSAGE_CHANNEL
     * )
     */
    public function execute($command);
}