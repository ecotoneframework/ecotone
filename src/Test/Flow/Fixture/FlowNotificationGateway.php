<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

use Ecotone\Api\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface FlowNotificationGateway
{
    #[MessageGateway('notification.send')]
    public function send(string $text): void;
}
