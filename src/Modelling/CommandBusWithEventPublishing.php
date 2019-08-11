<?php


namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\LazyEventBus\LazyEventPublishing;

/**
 * Interface CommandBusWithEventPublishing
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface CommandBusWithEventPublishing extends CommandBus
{
    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     *
     * @return mixed
     *
     * @Gateway(requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT)
     * @LazyEventPublishing()
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
     * @LazyEventPublishing()
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
     * @LazyEventPublishing()
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
     * @LazyEventPublishing()
     */
    public function convertAndSendWithMetadata(string $name, string $dataMediaType, $commandData, array $metadata);
}