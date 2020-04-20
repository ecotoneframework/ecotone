<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * @MessageEndpoint()
 */
interface CoinGateway
{
    /**
     * @MessageGateway(requestChannel="storeCoins")
     */
    public function store(int $coins);
}