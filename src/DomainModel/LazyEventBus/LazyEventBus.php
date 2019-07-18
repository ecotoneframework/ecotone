<?php


namespace SimplyCodedSoftware\DomainModel\LazyEventBus;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;

/**
 * Interface LazyEventBus
 * @package SimplyCodedSoftware\DomainModel
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