<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Interface QueryGateway
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface QueryGateway
{
    /**
     * Entrypoint for queries, when you have instance of query
     *
     * @param object $query instance of query
     *
     * @return mixed whatever query handler returns
     * @Gateway(
     *     requestChannel=AggregateMessage::AGGREGATE_SEND_MESSAGE_CHANNEL
     * )
     */
    public function execute($query);
}