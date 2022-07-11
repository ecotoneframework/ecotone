<?php

namespace Ecotone\EventSourcing\Config;

use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionExecutor;
use Ecotone\Messaging\Message;

class ProjectionFlowController
{
    private bool $requireContinuesPolling;

    public function __construct(bool $requireContinuesPolling)
    {
        $this->requireContinuesPolling = $requireContinuesPolling;
    }

    public function preSend(Message $message): ?Message
    {
        if ($this->requireContinuesPolling && ! $this->isPolling($message)) {
            return null;
        }

        return $message;
    }

    private function isPolling(Message $message)
    {
        return $message->getHeaders()->containsKey(ProjectionExecutor::PROJECTION_IS_POLLING)
            ? $message->getHeaders()->get(ProjectionExecutor::PROJECTION_IS_POLLING)
            : false;
    }
}
