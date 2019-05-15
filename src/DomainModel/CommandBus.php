<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeaderArray;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Interface CommandGateway
 * @package SimplyCodedSoftware\DomainModel
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
    public function send($command);

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
     *          @GatewayPayload(parameterName="command"),
     *          @GatewayHeaderArray(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata($command, array $metadata);

    /**
     * @param string $name
     * @param string $dataMediaType
     * @param mixed  $commandData
     *
     * @return mixed
     * @Gateway(
     *     requestChannel=CommandBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @GatewayHeader(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
     *          @GatewayHeader(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @GatewayPayload(parameterName="commandData")
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
     *          @GatewayHeaderArray(parameterName="metadata"),
     *          @GatewayHeader(parameterName="name", headerName=CommandBus::CHANNEL_NAME_BY_NAME),
     *          @GatewayHeader(parameterName="dataMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @GatewayPayload(parameterName="commandData")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $dataMediaType, $commandData, array $metadata);
}