<?php


namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Interface LazyEventBus
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface LazyEventBus
{
    const CHANNEL_NAME = "ecotone.modelling.bus.lazy_event";

    /**
     * Send event for lazy publish
     *
     * @param object $event instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=LazyEventBus::CHANNEL_NAME,
     *     parameterConverters={
     *         @Payload(parameterName="event"),
     *         @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata(object $event, array $metadata);
}