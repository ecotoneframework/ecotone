<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller;

use Ecotone\Messaging\Annotation\ServiceActivator;

class ServiceActivatorWithPollerExample
{
    #[ServiceActivator("inputChannel", "test-name")]
    public function sendMessage(): void
    {
    }
}