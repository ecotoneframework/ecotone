<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\MessageParameter;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Message;

interface MessagingEntrypoint
{
    const ENTRYPOINT = "ecotone.messaging.entrypoint";

    /**
     * @MessageGateway(
     *     requestChannel=MessagingEntrypoint::ENTRYPOINT,
     *     parameterConverters={
     *          @Payload(parameterName="payload"),
     *          @Header(parameterName="targetChannel", headerName=MessagingEntrypoint::ENTRYPOINT)
     *     }
     * )
     */
    public function send($payload, string $targetChannel);

    /**
     * @MessageGateway(
     *     requestChannel=MessagingEntrypoint::ENTRYPOINT,
     *     parameterConverters={
     *          @Payload(parameterName="payload"),
     *          @Headers(parameterName="headers"),
     *          @Header(parameterName="targetChannel", headerName=MessagingEntrypoint::ENTRYPOINT)
     *     }
     * )
     */
    public function sendWithHeaders($payload, array $headers, string $targetChannel);

    /**
     * It must contain {MessagingEntrypoint::ENTRYPOINT} header
     *
     * @MessageGateway(requestChannel=MessagingEntrypoint::ENTRYPOINT)
     */
    public function sendMessage(Message $message);
}