<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

interface ChannelReceivingStrategy
{
    public function decide(): string;
}
