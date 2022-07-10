<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\Message;

class FactoryActionRouter
{
    private string $channelName;

    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
    }

    public function route(Message $message) : void
    {

    }
}