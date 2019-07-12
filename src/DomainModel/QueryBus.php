<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Interface QueryBus
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
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
     * @Gateway(requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send($query);

    /**
     * Entrypoint for queries, when you access to instance of the command
     *
     * @param object $query instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_OBJECT,
     *     parameterConverters={
     *          @Payload(parameterName="query"),
     *          @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata($query, array $metadata);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $commandData
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=QueryBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSend(string $name, string $dataMediaType, $commandData);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $commandData
     * @param array  $metadata
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=QueryBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Headers(parameterName="metadata"),
     *          @Header(parameterName="name", headerName=QueryBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $dataMediaType, $commandData, array $metadata);
}