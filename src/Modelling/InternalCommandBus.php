<?php


namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

/**
 * Command bus without event publishing. Can be used inside command handler
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface InternalCommandBus
{
    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     *
     * @return mixed
     *
     * @MessageGateway(requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT)
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
     * @MessageGateway(
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
     * @param string $sourceMediaType
     * @param mixed  $commandData
     *
     * @return mixed
     * @MessageGateway(
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="sourceMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSend(string $name, string $sourceMediaType, $commandData);

    /**
     * @param string $name
     * @param string $sourceMediaType
     * @param mixed  $commandData
     * @param array  $metadata
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Headers(parameterName="metadata"),
     *          @Header(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="sourceMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $sourceMediaType, $commandData, array $metadata);
}