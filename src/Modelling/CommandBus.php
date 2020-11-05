<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\MessageHeaders;

interface CommandBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.command_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.command_by_name";

    /**
     * @return mixed
     */
    #[MessageGateway(CommandBus::CHANNEL_NAME_BY_OBJECT)]
    public function send(object $command);

    /**
     * @return mixed
     */
    #[MessageGateway(CommandBus::CHANNEL_NAME_BY_OBJECT)]
    public function sendWithMetadata(#[Payload] object $command, #[Headers] array $metadata);

    /**
     * @var mixed $commandData
     * @return mixed
     */
    #[MessageGateway(CommandBus::CHANNEL_NAME_BY_NAME)]
    public function convertAndSend(#[Header(CommandBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $commandData);

    /**
     * @var mixed $commandData
     * @return mixed
     */
    #[MessageGateway(CommandBus::CHANNEL_NAME_BY_NAME)]
    public function convertAndSendWithMetadata(#[Header(CommandBus::CHANNEL_NAME_BY_NAME)] string $name, #[Header(MessageHeaders::CONTENT_TYPE)] string $sourceMediaType, #[Payload] $commandData, #[Headers] array $metadata);
}