<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

interface QueryBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.query_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.query_by_name";

    /**
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function send(object $query);

    /**
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function sendWithMetadata(object $query, array $metadata);

    /**
     * @var mixed $data
     *
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSend(#[Header(QueryBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $dataMediaType, #[Payload] $data);

    /**
     * @var mixed $data
     *
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSendWithMetadata(#[Header(QueryBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $dataMediaType, #[Payload] $data, #[Headers] array $metadata);
}