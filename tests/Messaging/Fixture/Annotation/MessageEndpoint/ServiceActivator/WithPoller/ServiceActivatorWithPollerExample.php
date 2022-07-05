<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use Ecotone\Messaging\Attribute\ServiceActivator;

class ServiceActivatorWithPollerExample
{
    #[ServiceActivator("inputChannel", "test-name")]
    public function sendMessage(): void
    {
    }
}