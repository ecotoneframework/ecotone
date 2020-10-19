<?php


namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;


use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;

class MessageHeadersPropagator
{
    private array $currentlyPropagatedHeaders = [];

    public function storeHeaders(MethodInvocation $methodInvocation, Message $message)
    {
        $this->currentlyPropagatedHeaders[] = $message->getHeaders()->headers();

        try {
            $reply = $methodInvocation->proceed();
            array_shift($this->currentlyPropagatedHeaders);
        }catch (\Throwable $exception) {
            array_shift($this->currentlyPropagatedHeaders);

            throw $exception;
        }

        return $reply;
    }

    public function propagateHeaders(array $headers) : array
    {
        return array_merge($this->getLastHeaders(), $headers);
    }

    public function getLastHeaders() : array
    {
        $headers = end($this->currentlyPropagatedHeaders);

        if ($this->isCalledForFirstTime($headers)) {
            return [];
        }

        return $headers;
    }

    private function isCalledForFirstTime($headers): bool
    {
        return $headers === false;
    }
}