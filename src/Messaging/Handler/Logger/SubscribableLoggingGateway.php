<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Psr\Log\LoggerInterface;

/**
 * licence Apache-2.0
 */
interface SubscribableLoggingGateway
{
    public function registerLogger(?LoggerInterface $logger): void;
}
