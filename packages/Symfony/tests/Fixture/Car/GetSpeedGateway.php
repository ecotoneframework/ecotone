<?php

namespace Fixture\Car;

use Ecotone\Messaging\Attribute\MessageGateway;

interface GetSpeedGateway
{
    public const CHANNEL_NAME = 'getSpeedChannel';

    #[MessageGateway(GetSpeedGateway::CHANNEL_NAME)]
    public function getSpeed(): int;
}
