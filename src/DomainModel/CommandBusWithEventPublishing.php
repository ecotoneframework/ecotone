<?php


namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\LazyEventBus\LazyEventPublishing;
use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Interface CommandBusWithEventPublishing
 * @package SimplyCodedSoftware\DomainModel
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
     *          @Payload(parameterName="command"),
     *          @Headers(parameterName="metadata")
     *     }
     * )
     * @LazyEventPublishing()
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