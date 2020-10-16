<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\BeforeSend;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Throwable;

class BeforeSendChannelInterceptor implements ChannelInterceptor
{
    private \Ecotone\Messaging\Handler\NonProxyGateway $beforeSendGateway;

    public function __construct(NonProxyGateway $beforeSendGateway)
    {
        $this->beforeSendGateway = $beforeSendGateway;
    }

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $this->beforeSendGateway->execute([$message]);
    }

    /**
     * @inheritDoc
     */
    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
    }

    /**
     * @inheritDoc
     */
    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
    }

    /**
     * @inheritDoc
     */
    public function preReceive(MessageChannel $messageChannel): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
    }
}