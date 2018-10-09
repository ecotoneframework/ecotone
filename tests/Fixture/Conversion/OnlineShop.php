<?php
declare(strict_types=1);

namespace Fixture\Conversion;

/**
 * Class OnlineShop
 * @package Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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