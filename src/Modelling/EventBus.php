<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

interface EventBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";

    /**
     * @return mixed
     */
    #[MessageGateway(EventBus::CHANNEL_NAME_BY_OBJECT)]
    public function send(object $event);

    /**
     * @return mixed
     */
    #[MessageGateway(EventBus::CHANNEL_NAME_BY_OBJECT)]
    public function sendWithMetadata(object $event, array $metadata);


    /**
     * @var mixed $data
     * @return mixed
     */
    #[MessageGateway(EventBus::CHANNEL_NAME_BY_NAME)]
    public function convertAndSend(#[Header(EventBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $data);

    /**
     * @var mixed $data
     * @return mixed
     */
    #[MessageGateway(EventBus::CHANNEL_NAME_BY_NAME)]
    public function convertAndSendWithMetadata(#[Header(EventBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $data, #[Headers] array $metadata);
}