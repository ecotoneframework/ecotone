<?php

namespace Ecotone\EventSourcing;

class ProjectionEventHandlerConfiguration
{
    public function __construct(private string $className, private string $methodName, private string $synchronousRequestChannelName, private string $asynchronousRequestChannelName)
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getSynchronousRequestChannelName(): string
    {
        return $this->synchronousRequestChannelName;
    }

    public function getTriggeringChannelName(): string
    {
        return $this->asynchronousRequestChannelName;
    }
}
