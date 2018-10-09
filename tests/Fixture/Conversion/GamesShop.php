<?php
declare(strict_types=1);

namespace Fixture\Conversion;

/**
 * Class GamesShop
 * @package Fixture\Conversion
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