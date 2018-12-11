<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

/**
 * Class OnlineShop
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
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