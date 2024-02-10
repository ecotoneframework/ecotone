<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Messaging\Message;

interface ChannelSendingStrategy
{
    public function decideFor(Message $message): string;
}
