<?php

namespace Ecotone\Messaging\Handler\Chain;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface KeeperGateway
{
    public function execute(Message $message): ?Message;
}
