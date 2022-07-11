<?php

namespace Ecotone\Enqueue;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

interface ReconnectableConnectionFactory extends ConnectionFactory
{
    public function isDisconnected(?Context $context): bool;

    public function reconnect(): void;

    public function getConnectionInstanceId(): int;
}
