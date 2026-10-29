<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Conversion;

/**
 * Class OnlineShop
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class OnlineShop extends GamesShop implements Shop
{
    /**
     * @inheritDoc
     */
    public function buy($productId): void
    {
        // TODO: Implement buy() method.
    }

    /**
     * @inheritDoc
     */
    public function findGames($gameId)
    {
        // TODO: Implement findGame() method.
    }
}
