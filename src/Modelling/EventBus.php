<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

interface EventBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";

    /**
     * @MessageGateway(requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function send(object $event);

    /**
     * @MessageGateway(requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function sendWithMetadata(object $event, array $metadata);


    /**
     * @var mixed $data
     *
     * @MessageGateway(requestChannel=EventBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSend(#[Header(EventBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $data);

    /**
     * @var mixed $data
     *
     * @MessageGateway(requestChannel=EventBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSendWithMetadata(#[Header(EventBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $data, #[Headers] array $metadata);
}