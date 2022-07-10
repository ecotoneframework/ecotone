<?php

namespace Fixture\Car;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\MessageGateway;

interface IncreaseSpeedGateway
{
    const CHANNEL_NAME = 'speedChannel';

    #[MessageGateway(IncreaseSpeedGateway::CHANNEL_NAME)]
    public function increaseSpeed(int $amount) : void;
}