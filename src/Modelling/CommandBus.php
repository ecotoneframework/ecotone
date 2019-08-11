<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

/**
 * Interface CommandGateway
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface CommandBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.command_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.command_by_name";

    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     *
     * @return mixed
     *
     * @Gateway(requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send(object $command);

    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @Gateway(
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT,
     *     parameterConverters={
     *          @Payload(parameterName="command"),
     *          @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata(object $command, array $metadata);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $commandData
     *
     * @return mixed
     * @Gateway(
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
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
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Headers(parameterName="metadata"),
     *          @Header(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $dataMediaType, $commandData, array $metadata);
}