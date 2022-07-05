<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;

interface CoinGateway
{
    #[MessageGateway("storeCoins")]
    public function store(int $coins);
}