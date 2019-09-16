<?php


namespace Ecotone\Messaging\Handler\Bridge;

use Ecotone\Messaging\Message;

/**
 * Class Bridge
 * @package Ecotone\Messaging\Handler\Bridge
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Bridge
{
    public function handle(Message $message) : Message
    {
        return $message;
    }
}