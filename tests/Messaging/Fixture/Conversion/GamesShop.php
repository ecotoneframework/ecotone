<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

/**
 * Class GamesShop
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class GamesShop
{
    /**
     * @param string $gameId
     * @return \stdClass[]
     */
    public abstract function findGames($gameId);
}