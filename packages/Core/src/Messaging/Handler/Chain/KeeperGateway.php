<?php


namespace Ecotone\Messaging\Handler\Chain;

use Ecotone\Messaging\Message;

interface KeeperGateway
{
    public function execute(Message $message) : ?Message;
}