<?php


namespace Ecotone\Messaging\Handler\Logger;


use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Message;

class QuickLogger
{
    public static function createAsServiceActivator() : ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self(), "log");
    }

    public function log(Message $message) : Message
    {
        echo "\nMessage with id: " . $message->getHeaders()->getMessageId() . " arrived.\n";

        return $message;
    }
}