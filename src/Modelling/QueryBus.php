<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

interface QueryBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.query_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.query_by_name";

    /**
     * Entrypoint for queries, when you access to instance of the command
     *
     * @param object $query instance of command
     *
     * @return mixed
     *
     * @MessageGateway(requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send(object $query);

    /**
     * Entrypoint for queries, when you access to instance of the command
     *
     * @param object $query instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT,
     *     parameterConverters={
     *          @Payload(parameterName="query"),
     *          @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata(object $query, array $metadata);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $data
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=QueryBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="data")
     *     }
     * )
     */
    public function convertAndSend(string $name, string $dataMediaType, $data);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $data
     * @param array  $metadata
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Headers(parameterName="metadata"),
     *          @Header(parameterName="name", headerName=QueryBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="data")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $dataMediaType, $data, array $metadata);
}