<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

/**
 * Interface EventBus
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface EventBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";

    /**
     * Entrypoint for queries, when you access to instance of the command
     *
     * @param object $event instance of command
     *
     * @return mixed
     *
     * @Gateway(requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send($event);

    /**
     * Entrypoint for queries, when you access to instance of the command
     *
     * @param object $event instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT,
     *     parameterConverters={
     *         @Payload(parameterName="event"),
     *         @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata($event, array $metadata);
}