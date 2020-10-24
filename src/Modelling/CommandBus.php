<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\LazyEventBus\LazyEventPublishing;

/**
 * @LazyEventPublishing()
 */
interface CommandBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.command_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.command_by_name";

    /**
     * @MessageGateway(requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function send(object $command);

    /**
     * @MessageGateway(requestChannel=CommandBus::CHANNEL_NAME_BY_OBJECT)
     *
     * @return mixed
     */
    public function sendWithMetadata(#[Payload] object $command, #[Headers] array $metadata);

    /**
     * @var mixed $commandData
     *
     * @MessageGateway(requestChannel=CommandBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSend(#[Header(CommandBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $commandData);

    /**
     * @var mixed $commandData
     *
     * @MessageGateway(requestChannel=CommandBus::CHANNEL_NAME_BY_NAME)
     *
     * @return mixed
     */
    public function convertAndSendWithMetadata(#[Header(CommandBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $commandData, #[Headers] array $metadata);
}