<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Conversion;

use stdClass;

/**
 * Class GamesShop
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class GamesShop
{
    /**
     * @param string $gameId
     * @return stdClass[]
     */
    abstract public function findGames($gameId);
}
