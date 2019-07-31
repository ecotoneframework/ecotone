<?php

namespace Ecotone\DomainModel;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Interface EventBus
 * @package Ecotone\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface EventBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";

    /**
     * Entrypoint for events, when you access to instance of the command
     *
     * @param object $event instance of command
     *
     * @return mixed
     *
     * @Gateway(requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send(object $event);

    /**
     * Entrypoint for events, when you access to instance of the command
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
    public function sendWithMetadata(object $event, array $metadata);
}