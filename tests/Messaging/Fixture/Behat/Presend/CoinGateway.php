<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface CoinGateway
{
    #[MessageGateway('storeCoins')]
    public function store(int $coins);
}
